<?php

namespace App\Strategies;

class EmaCrossoverStrategy implements StrategyInterface
{
    /**
     * Evaluate the given OHLCV candles and return a signal.
     * 
     * @param array $candles [ [timestamp, open, high, low, close, volume], ... ]
     * @param array $parameters User defined parameters
     * @return string 'BUY', 'SELL', or 'HOLD'
     */
    public function evaluate(array $candles, array $parameters): string
    {
        $fastPeriod = $parameters['ema_fast'] ?? 9;
        $slowPeriod = $parameters['ema_slow'] ?? 21;

        // We need enough candles to compute the slow EMA and check crossovers
        if (count($candles) < $slowPeriod + 5) {
            return 'HOLD'; // Not enough data
        }

        $closes = array_column($candles, 4);

        $fastEma = $this->calculateEma($closes, $fastPeriod);
        $slowEma = $this->calculateEma($closes, $slowPeriod);

        // We check signals strictly on the closed confirmation candle.
        // Current open/live candle: count($candles) - 1
        // Confirmed closed candle: count($candles) - 2
        // Previous closed candle: count($candles) - 3
        $idxCurrent = count($candles) - 2;
        $idxPrevious = count($candles) - 3;

        if (!isset($fastEma[$idxCurrent], $slowEma[$idxCurrent], $fastEma[$idxPrevious], $slowEma[$idxPrevious])) {
            return 'HOLD';
        }

        $currentFast = $fastEma[$idxCurrent];
        $currentSlow = $slowEma[$idxCurrent];
        $previousFast = $fastEma[$idxPrevious];
        $previousSlow = $slowEma[$idxPrevious];

        // Golden Cross: Fast EMA crosses ABOVE Slow EMA
        if ($previousFast <= $previousSlow && $currentFast > $currentSlow) {
            return 'BUY';
        }

        // Death Cross: Fast EMA crosses BELOW Slow EMA
        if ($previousFast >= $previousSlow && $currentFast < $currentSlow) {
            return 'SELL';
        }

        return 'HOLD';
    }

    /**
     * Get the historical indicator data for visualization.
     * 
     * @param array $candles [ [timestamp, open, high, low, close, volume], ... ]
     * @param array $parameters User defined parameters
     * @return array Array containing the strategy type and time-series data
     */
    public function getChartData(array $candles, array $parameters): array
    {
        $fastPeriod = $parameters['ema_fast'] ?? 9;
        $slowPeriod = $parameters['ema_slow'] ?? 21;

        if (count($candles) < $slowPeriod) {
            return ['type' => 'EMA', 'data' => []];
        }

        $closes = array_column($candles, 4);
        $times = array_column($candles, 0);

        $fastEma = $this->calculateEma($closes, $fastPeriod);
        $slowEma = $this->calculateEma($closes, $slowPeriod);

        $fastData = [];
        $slowData = [];
        $signals = [];

        foreach ($fastEma as $i => $val) {
            $time = (int)floor($times[$i] / 1000);
            $fastData[] = ['time' => $time, 'value' => $val];
        }

        foreach ($slowEma as $i => $val) {
            $time = (int)floor($times[$i] / 1000);
            $slowData[] = ['time' => $time, 'value' => $val];
        }

        // Find crossovers for chart visual markers
        for ($i = $slowPeriod; $i < count($closes) - 1; $i++) {
            $time = (int)floor($times[$i] / 1000);
            
            $currentFast = $fastEma[$i] ?? null;
            $currentSlow = $slowEma[$i] ?? null;
            $previousFast = $fastEma[$i - 1] ?? null;
            $previousSlow = $slowEma[$i - 1] ?? null;

            if ($currentFast !== null && $currentSlow !== null && $previousFast !== null && $previousSlow !== null) {
                // Golden Cross
                if ($previousFast <= $previousSlow && $currentFast > $currentSlow) {
                    $signals[] = [
                        'time' => $time,
                        'position' => 'belowBar',
                        'color' => '#00e676',
                        'shape' => 'arrowUp',
                        'text' => 'BUY'
                    ];
                }
                // Death Cross
                elseif ($previousFast >= $previousSlow && $currentFast < $currentSlow) {
                    $signals[] = [
                        'time' => $time,
                        'position' => 'aboveBar',
                        'color' => '#ff3d00',
                        'shape' => 'arrowDown',
                        'text' => 'SELL'
                    ];
                }
            }
        }

        return [
            'type' => 'EMA',
            'data' => [
                'fast' => $fastData,
                'slow' => $slowData
            ],
            'signals' => $signals
        ];
    }

    /**
     * Compute Exponential Moving Average (EMA)
     */
    private function calculateEma(array $prices, int $period): array
    {
        $ema = [];
        if (count($prices) < $period) {
            return [];
        }

        $alpha = 2 / ($period + 1);

        // First EMA value is SMA of the first period
        $sum = 0;
        for ($i = 0; $i < $period; $i++) {
            $sum += $prices[$i];
        }
        $currentEma = $sum / $period;
        $ema[$period - 1] = $currentEma;

        // Apply recursive formula for remaining values
        for ($i = $period; $i < count($prices); $i++) {
            $currentEma = ($prices[$i] * $alpha) + ($currentEma * (1 - $alpha));
            $ema[$i] = $currentEma;
        }

        return $ema;
    }
}
