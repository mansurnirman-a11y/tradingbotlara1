<?php

namespace App\Strategies;

class MacdStrategy implements StrategyInterface
{
    public function evaluate(array $candles, array $parameters): string
    {
        $fastPeriod = $parameters['macd_fast'] ?? 12;
        $slowPeriod = $parameters['macd_slow'] ?? 26;
        $signalPeriod = $parameters['macd_signal'] ?? 9;

        // Need enough data for the slow period + signal period + some smoothing buffer (e.g. total 50 candles)
        if (count($candles) < $slowPeriod + $signalPeriod + 10) {
            return 'HOLD';
        }

        $closes = array_column($candles, 4);

        $macdLine = $this->calculateMacdLine($closes, $fastPeriod, $slowPeriod);
        $signalLine = $this->calculateSignalLine($macdLine, $signalPeriod);

        if (count($macdLine) < 2 || count($signalLine) < 2) {
            return 'HOLD';
        }

        $currentMacd = end($macdLine);
        $previousMacd = prev($macdLine);
        
        $currentSignal = end($signalLine);
        $previousSignal = prev($signalLine);

        // Bullish Crossover: MACD crosses ABOVE Signal
        if ($previousMacd <= $previousSignal && $currentMacd > $currentSignal) {
            return 'BUY';
        }

        // Bearish Crossover: MACD crosses BELOW Signal
        if ($previousMacd >= $previousSignal && $currentMacd < $currentSignal) {
            return 'SELL';
        }

        return 'HOLD';
    }

    public function getChartData(array $candles, array $parameters): array
    {
        $fastPeriod = $parameters['macd_fast'] ?? 12;
        $slowPeriod = $parameters['macd_slow'] ?? 26;
        $signalPeriod = $parameters['macd_signal'] ?? 9;

        if (count($candles) < $slowPeriod + $signalPeriod + 10) {
            return ['type' => 'MACD', 'data' => []];
        }

        $closes = array_column($candles, 4);
        $times = array_column($candles, 0);

        $macdLine = $this->calculateMacdLine($closes, $fastPeriod, $slowPeriod);
        $signalLine = $this->calculateSignalLine($macdLine, $signalPeriod);

        $macdData = [];
        $signalData = [];
        $histogramData = [];
        $signals = [];

        // The MACD line array is smaller than the original closes by ($slowPeriod - 1)
        $macdOffset = count($closes) - count($macdLine);
        // The Signal line array is smaller than MACD line by ($signalPeriod - 1)
        $signalOffset = count($macdLine) - count($signalLine);

        $previousMacdVal = null;
        $previousSigVal = null;

        for ($i = 0; $i < count($signalLine); $i++) {
            $candleIndex = $macdOffset + $signalOffset + $i;
            $time = (int)floor($times[$candleIndex] / 1000);

            $macdVal = $macdLine[$signalOffset + $i];
            $sigVal = $signalLine[$i];
            $histVal = $macdVal - $sigVal;

            $macdData[] = ['time' => $time, 'value' => $macdVal];
            $signalData[] = ['time' => $time, 'value' => $sigVal];
            $histogramData[] = [
                'time' => $time, 
                'value' => $histVal, 
                'color' => $histVal >= 0 ? 'rgba(0, 230, 118, 0.5)' : 'rgba(255, 61, 0, 0.5)'
            ];

            // Evaluate Crossovers
            if ($previousMacdVal !== null && $previousSigVal !== null) {
                if ($previousMacdVal <= $previousSigVal && $macdVal > $sigVal) {
                    $signals[] = [
                        'time' => $time,
                        'position' => 'belowBar',
                        'color' => '#00e676',
                        'shape' => 'arrowUp',
                        'text' => 'BUY Signal'
                    ];
                } elseif ($previousMacdVal >= $previousSigVal && $macdVal < $sigVal) {
                    $signals[] = [
                        'time' => $time,
                        'position' => 'aboveBar',
                        'color' => '#ff3d00',
                        'shape' => 'arrowDown',
                        'text' => 'SELL Signal'
                    ];
                }
            }

            $previousMacdVal = $macdVal;
            $previousSigVal = $sigVal;
        }

        return [
            'type' => 'MACD',
            'data' => [
                'macd' => $macdData,
                'signal' => $signalData,
                'histogram' => $histogramData
            ],
            'signals' => $signals
        ];
    }

    private function calculateEma(array $prices, int $period): array
    {
        $emas = [];
        $multiplier = 2 / ($period + 1);
        
        // Initial SMA for the first EMA calculation
        $sma = array_sum(array_slice($prices, 0, $period)) / $period;
        $emas[] = $sma;

        for ($i = $period; $i < count($prices); $i++) {
            $currentEma = ($prices[$i] - end($emas)) * $multiplier + end($emas);
            $emas[] = $currentEma;
        }

        return $emas;
    }

    private function calculateMacdLine(array $closes, int $fastPeriod, int $slowPeriod): array
    {
        $fastEma = $this->calculateEma($closes, $fastPeriod);
        $slowEma = $this->calculateEma($closes, $slowPeriod);

        // Align arrays so they end at the same candle
        $fastEma = array_slice($fastEma, -count($slowEma));

        $macdLine = [];
        for ($i = 0; $i < count($slowEma); $i++) {
            $macdLine[] = $fastEma[$i] - $slowEma[$i];
        }

        return $macdLine;
    }

    private function calculateSignalLine(array $macdLine, int $signalPeriod): array
    {
        return $this->calculateEma($macdLine, $signalPeriod);
    }
}
