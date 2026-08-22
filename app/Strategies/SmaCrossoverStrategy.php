<?php

namespace App\Strategies;

class SmaCrossoverStrategy implements StrategyInterface
{
    public function evaluate(array $candles, array $parameters): string
    {
        $fastPeriod = $parameters['sma_fast'] ?? 9;
        $slowPeriod = $parameters['sma_slow'] ?? 21;

        if (count($candles) < $slowPeriod + 1) {
            return 'HOLD'; // Not enough data
        }

        $closes = array_column($candles, 4); // 4th index is Close price in CCXT
        
        // We need the previous and current SMAs to detect a crossover
        $currentFastSma = $this->calculateSma(array_slice($closes, -$fastPeriod), $fastPeriod);
        $currentSlowSma = $this->calculateSma(array_slice($closes, -$slowPeriod), $slowPeriod);

        // Previous period SMAs (excluding the last candle)
        $previousFastSma = $this->calculateSma(array_slice($closes, -($fastPeriod + 1), $fastPeriod), $fastPeriod);
        $previousSlowSma = $this->calculateSma(array_slice($closes, -($slowPeriod + 1), $slowPeriod), $slowPeriod);

        // Golden Cross: Fast crosses ABOVE Slow
        if ($previousFastSma <= $previousSlowSma && $currentFastSma > $currentSlowSma) {
            return 'BUY';
        }

        // Death Cross: Fast crosses BELOW Slow
        if ($previousFastSma >= $previousSlowSma && $currentFastSma < $currentSlowSma) {
            return 'SELL';
        }

        return 'HOLD';
    }

    public function getChartData(array $candles, array $parameters): array
    {
        $fastPeriod = $parameters['sma_fast'] ?? 9;
        $slowPeriod = $parameters['sma_slow'] ?? 21;

        if (count($candles) < $slowPeriod) {
            return ['type' => 'SMA', 'data' => []];
        }

        $closes = array_column($candles, 4);
        $times = array_column($candles, 0);

        $fastData = [];
        for ($i = $fastPeriod - 1; $i < count($closes); $i++) {
            $slice = array_slice($closes, $i - $fastPeriod + 1, $fastPeriod);
            $sma = array_sum($slice) / $fastPeriod;
            $fastData[] = ['time' => (int)floor($times[$i] / 1000), 'value' => $sma];
        }

        $slowData = [];
        $signals = [];
        
        $previousFast = null;
        $previousSlow = null;

        for ($i = $slowPeriod - 1; $i < count($closes); $i++) {
            $slice = array_slice($closes, $i - $slowPeriod + 1, $slowPeriod);
            $slowSma = array_sum($slice) / $slowPeriod;
            $time = (int)floor($times[$i] / 1000);
            
            $slowData[] = ['time' => $time, 'value' => $slowSma];

            // Find matching fastSma
            $fastSma = null;
            foreach ($fastData as $fd) {
                if ($fd['time'] === $time) {
                    $fastSma = $fd['value'];
                    break;
                }
            }

            if ($fastSma !== null) {
                if ($previousFast !== null && $previousSlow !== null) {
                    if ($previousFast <= $previousSlow && $fastSma > $slowSma) {
                        $signals[] = [
                            'time' => $time,
                            'position' => 'belowBar',
                            'color' => '#00e676',
                            'shape' => 'arrowUp',
                            'text' => 'BUY Signal'
                        ];
                    } elseif ($previousFast >= $previousSlow && $fastSma < $slowSma) {
                        $signals[] = [
                            'time' => $time,
                            'position' => 'aboveBar',
                            'color' => '#ff3d00',
                            'shape' => 'arrowDown',
                            'text' => 'SELL Signal'
                        ];
                    }
                }
                $previousFast = $fastSma;
                $previousSlow = $slowSma;
            }
        }

        return [
            'type' => 'SMA',
            'data' => [
                'fast' => $fastData,
                'slow' => $slowData
            ],
            'signals' => $signals
        ];
    }

    private function calculateSma(array $prices, int $period): float
    {
        return array_sum($prices) / $period;
    }
}
