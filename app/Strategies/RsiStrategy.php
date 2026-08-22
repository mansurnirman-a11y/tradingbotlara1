<?php

namespace App\Strategies;

class RsiStrategy implements StrategyInterface
{
    public function evaluate(array $candles, array $parameters): string
    {
        $period = $parameters['rsi_period'] ?? 14;
        
        if (count($candles) < $period + 1) {
            return 'HOLD'; // Not enough data
        }

        // Basic RSI calculation in pure PHP
        $closes = array_column($candles, 4); // 4th index is Close price in CCXT
        
        $gains = 0;
        $losses = 0;

        // Calculate initial average gain/loss
        for ($i = 1; $i <= $period; $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            if ($change > 0) {
                $gains += $change;
            } else {
                $losses -= $change; // absolute value
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        // Smooth it out for remaining candles
        for ($i = $period + 1; $i < count($closes); $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            if ($change > 0) {
                $avgGain = (($avgGain * ($period - 1)) + $change) / $period;
                $avgLoss = ($avgLoss * ($period - 1)) / $period;
            } else {
                $avgGain = ($avgGain * ($period - 1)) / $period;
                $avgLoss = (($avgLoss * ($period - 1)) - $change) / $period;
            }
        }

        if ($avgLoss == 0) {
            $rsi = 100;
        } else {
            $rs = $avgGain / $avgLoss;
            $rsi = 100 - (100 / (1 + $rs));
        }

        // Basic Logic: Oversold = BUY, Overbought = SELL
        if ($rsi < 30) {
            return 'BUY';
        } elseif ($rsi > 70) {
            return 'SELL';
        }

        return 'HOLD';
    }

    public function getChartData(array $candles, array $parameters): array
    {
        $period = $parameters['rsi_period'] ?? 14;
        
        if (count($candles) < $period + 1) {
            return ['type' => 'RSI', 'data' => []];
        }

        $closes = array_column($candles, 4);
        $times = array_column($candles, 0); // Need original timestamps for graph
        
        $gains = 0;
        $losses = 0;

        for ($i = 1; $i <= $period; $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            if ($change > 0) {
                $gains += $change;
            } else {
                $losses -= $change;
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        $rsiSeries = [];

        // First RSI value
        if ($avgLoss == 0) {
            $rsi = 100;
        } else {
            $rs = $avgGain / $avgLoss;
            $rsi = 100 - (100 / (1 + $rs));
        }
        $rsiSeries[] = ['time' => (int)floor($times[$period] / 1000), 'value' => $rsi];

        // Smooth it out for remaining candles
        for ($i = $period + 1; $i < count($closes); $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            if ($change > 0) {
                $avgGain = (($avgGain * ($period - 1)) + $change) / $period;
                $avgLoss = ($avgLoss * ($period - 1)) / $period;
            } else {
                $avgGain = ($avgGain * ($period - 1)) / $period;
                $avgLoss = (($avgLoss * ($period - 1)) - $change) / $period;
            }

            if ($avgLoss == 0) {
                $rsi = 100;
            } else {
                $rs = $avgGain / $avgLoss;
                $rsi = 100 - (100 / (1 + $rs));
            }

            $rsiSeries[] = ['time' => (int)floor($times[$i] / 1000), 'value' => $rsi];
        }

        // Strictly order by time
        usort($rsiSeries, function($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        // Deduplicate and Generate Signals
        $unique = [];
        $signals = [];
        $last = 0;
        
        foreach ($rsiSeries as $p) {
            if ($p['time'] > $last) {
                $unique[] = $p;
                
                // Evaluate Signal for graph
                if ($p['value'] < 30) {
                    $signals[] = [
                        'time' => $p['time'],
                        'position' => 'belowBar',
                        'color' => '#00e676',
                        'shape' => 'arrowUp',
                        'text' => 'BUY Signal'
                    ];
                } elseif ($p['value'] > 70) {
                    $signals[] = [
                        'time' => $p['time'],
                        'position' => 'aboveBar',
                        'color' => '#ff3d00',
                        'shape' => 'arrowDown',
                        'text' => 'SELL Signal'
                    ];
                }
                
                $last = $p['time'];
            }
        }

        return [
            'type' => 'RSI', 
            'data' => $unique,
            'signals' => $signals
        ];
    }
}
