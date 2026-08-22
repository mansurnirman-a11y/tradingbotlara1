<?php

namespace App\Strategies;

class BollingerScalpingStrategy implements StrategyInterface
{
    public function evaluate(array $candles, array $parameters): string
    {
        $period = $parameters['bb_period'] ?? 20;
        $multiplier = $parameters['bb_multiplier'] ?? 2.0;

        if (count($candles) < $period + 1) {
            return 'HOLD';
        }

        $closes = array_column($candles, 4);
        
        // We need the current and previous candles to check crossovers
        $currentClose = $closes[count($closes) - 1];
        $previousClose = $closes[count($closes) - 2];

        // Calculate Bollinger Bands for the current candle (using previous $period candles)
        $currentSlice = array_slice($closes, -$period);
        $currentBands = $this->calculateBollingerBands($currentSlice, $multiplier);

        // Calculate Bollinger Bands for the previous candle
        $previousSlice = array_slice($closes, -($period + 1), $period);
        $previousBands = $this->calculateBollingerBands($previousSlice, $multiplier);

        // BUY Signal: Previous close was above Lower Band, Current close is below Lower Band
        if ($previousClose >= $previousBands['lower'] && $currentClose < $currentBands['lower']) {
            return 'BUY';
        }

        // SELL Signal: Previous close was below Upper Band, Current close is above Upper Band
        if ($previousClose <= $previousBands['upper'] && $currentClose > $currentBands['upper']) {
            return 'SELL';
        }

        return 'HOLD';
    }

    public function getChartData(array $candles, array $parameters): array
    {
        $period = $parameters['bb_period'] ?? 20;
        $multiplier = $parameters['bb_multiplier'] ?? 2.0;

        if (count($candles) < $period) {
            return ['type' => 'BOLLINGER', 'data' => []];
        }

        $closes = array_column($candles, 4);
        $times = array_column($candles, 0);

        $upperData = [];
        $lowerData = [];
        $middleData = [];
        $signals = [];

        $previousClose = null;
        $previousUpper = null;
        $previousLower = null;

        for ($i = $period - 1; $i < count($closes); $i++) {
            $slice = array_slice($closes, $i - $period + 1, $period);
            $bands = $this->calculateBollingerBands($slice, $multiplier);
            
            $time = (int)floor($times[$i] / 1000);
            $close = $closes[$i];

            $upperData[] = ['time' => $time, 'value' => $bands['upper']];
            $lowerData[] = ['time' => $time, 'value' => $bands['lower']];
            $middleData[] = ['time' => $time, 'value' => $bands['middle']];

            // Evaluate Signals for graph
            if ($previousClose !== null && $previousUpper !== null && $previousLower !== null) {
                if ($previousClose >= $previousLower && $close < $bands['lower']) {
                    $signals[] = [
                        'time' => $time,
                        'position' => 'belowBar',
                        'color' => '#00e676',
                        'shape' => 'arrowUp',
                        'text' => 'BUY Signal'
                    ];
                } elseif ($previousClose <= $previousUpper && $close > $bands['upper']) {
                    $signals[] = [
                        'time' => $time,
                        'position' => 'aboveBar',
                        'color' => '#ff3d00',
                        'shape' => 'arrowDown',
                        'text' => 'SELL Signal'
                    ];
                }
            }

            $previousClose = $close;
            $previousUpper = $bands['upper'];
            $previousLower = $bands['lower'];
        }

        return [
            'type' => 'BOLLINGER',
            'data' => [
                'upper' => $upperData,
                'lower' => $lowerData,
                'middle' => $middleData
            ],
            'signals' => $signals
        ];
    }

    private function calculateBollingerBands(array $prices, float $multiplier): array
    {
        $period = count($prices);
        $mean = array_sum($prices) / $period;
        
        $variance = 0;
        foreach ($prices as $price) {
            $variance += pow($price - $mean, 2);
        }
        $variance /= $period;
        $stdDev = sqrt($variance);

        return [
            'middle' => $mean,
            'upper' => $mean + ($multiplier * $stdDev),
            'lower' => $mean - ($multiplier * $stdDev)
        ];
    }
}
