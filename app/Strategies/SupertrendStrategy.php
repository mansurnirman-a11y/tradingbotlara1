<?php

namespace App\Strategies;

class SupertrendStrategy implements StrategyInterface
{
    /**
     * Evaluate the given OHLCV candles and return a signal.
     * 
     * @param array $candles [ [timestamp, open, high, low, close, volume], ... ]
     * @param array $parameters User defined parameters (e.g. ['atr_period' => 10, 'multiplier' => 3.0])
     * @return string 'BUY', 'SELL', or 'HOLD'
     */
    public function evaluate(array $candles, array $parameters): string
    {
        $atrPeriod = (int)($parameters['atr_period'] ?? 10);
        $multiplier = (float)($parameters['multiplier'] ?? 3.0);

        if (count($candles) < $atrPeriod + 5) {
            return 'HOLD';
        }

        $supertrendData = $this->calculateSupertrend($candles, $atrPeriod, $multiplier);

        if (empty($supertrendData) || count($supertrendData) < 3) {
            return 'HOLD';
        }

        // We check signals strictly on the closed confirmation candle.
        // Current open/live candle: count - 1
        // Confirmed closed candle: count - 2
        // Previous closed candle: count - 3
        $idxCurrent = count($supertrendData) - 2;
        $idxPrevious = count($supertrendData) - 3;

        $current = $supertrendData[$idxCurrent];
        $previous = $supertrendData[$idxPrevious];

        // Trend flip: 1 is Bullish (Green), -1 is Bearish (Red)
        if ($previous['direction'] === -1 && $current['direction'] === 1) {
            return 'BUY'; // Fresh Bullish Supertrend Signal
        }

        if ($previous['direction'] === 1 && $current['direction'] === -1) {
            return 'SELL'; // Fresh Bearish Supertrend Signal
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
        $atrPeriod = (int)($parameters['atr_period'] ?? 10);
        $multiplier = (float)($parameters['multiplier'] ?? 3.0);

        $supertrendData = $this->calculateSupertrend($candles, $atrPeriod, $multiplier);

        $lineData = [];
        $signals = [];

        foreach ($supertrendData as $i => $item) {
            $time = (int)floor($item['time'] / 1000);
            $lineData[] = [
                'time' => $time,
                'value' => $item['supertrend'],
                'color' => $item['direction'] === 1 ? '#00e676' : '#ff3d00',
            ];

            if ($i > 0) {
                $prev = $supertrendData[$i - 1];
                if ($prev['direction'] === -1 && $item['direction'] === 1) {
                    $signals[] = [
                        'time' => $time,
                        'position' => 'belowBar',
                        'color' => '#00e676',
                        'shape' => 'arrowUp',
                        'text' => 'BUY (ST)'
                    ];
                } elseif ($prev['direction'] === 1 && $item['direction'] === -1) {
                    $signals[] = [
                        'time' => $time,
                        'position' => 'aboveBar',
                        'color' => '#ff3d00',
                        'shape' => 'arrowDown',
                        'text' => 'SELL (ST)'
                    ];
                }
            }
        }

        return [
            'type' => 'Supertrend',
            'data' => $lineData,
            'signals' => $signals
        ];
    }

    /**
     * Calculate SuperTrend indicator for the candle series.
     */
    private function calculateSupertrend(array $candles, int $atrPeriod, float $multiplier): array
    {
        $count = count($candles);
        if ($count <= $atrPeriod) {
            return [];
        }

        // 1. Calculate True Range (TR)
        $tr = [];
        $tr[0] = $candles[0][2] - $candles[0][3]; // High - Low for first candle

        for ($i = 1; $i < $count; $i++) {
            $high = $candles[$i][2];
            $low = $candles[$i][3];
            $prevClose = $candles[$i - 1][4];

            $hl = $high - $low;
            $hpc = abs($high - $prevClose);
            $lpc = abs($low - $prevClose);

            $tr[$i] = max($hl, $hpc, $lpc);
        }

        // 2. Calculate RMA / Wilder's ATR
        $atr = [];
        $sum = 0;
        for ($i = 0; $i < $atrPeriod; $i++) {
            $sum += $tr[$i];
        }
        $atr[$atrPeriod - 1] = $sum / $atrPeriod;

        for ($i = $atrPeriod; $i < $count; $i++) {
            $atr[$i] = (($atr[$i - 1] * ($atrPeriod - 1)) + $tr[$i]) / $atrPeriod;
        }

        // 3. Calculate Supertrend Bands
        $results = [];
        $prevFinalUpper = 0;
        $prevFinalLower = 0;
        $prevSupertrend = 0;
        $prevDirection = 1; // 1 = Bullish, -1 = Bearish

        for ($i = $atrPeriod - 1; $i < $count; $i++) {
            $time = $candles[$i][0];
            $high = $candles[$i][2];
            $low = $candles[$i][3];
            $close = $candles[$i][4];
            $hl2 = ($high + $low) / 2;
            $currentAtr = $atr[$i];

            $basicUpper = $hl2 + ($multiplier * $currentAtr);
            $basicLower = $hl2 - ($multiplier * $currentAtr);

            if ($i === $atrPeriod - 1) {
                $finalUpper = $basicUpper;
                $finalLower = $basicLower;
                $direction = ($close >= $hl2) ? 1 : -1;
                $supertrend = ($direction === 1) ? $finalLower : $finalUpper;
            } else {
                $prevClose = $candles[$i - 1][4];

                // Final Upper Band
                if ($basicUpper < $prevFinalUpper || $prevClose > $prevFinalUpper) {
                    $finalUpper = $basicUpper;
                } else {
                    $finalUpper = $prevFinalUpper;
                }

                // Final Lower Band
                if ($basicLower > $prevFinalLower || $prevClose < $prevFinalLower) {
                    $finalLower = $basicLower;
                } else {
                    $finalLower = $prevFinalLower;
                }

                // Direction & Supertrend value
                if ($prevSupertrend == $prevFinalUpper) {
                    if ($close > $finalUpper) {
                        $direction = 1;
                        $supertrend = $finalLower;
                    } else {
                        $direction = -1;
                        $supertrend = $finalUpper;
                    }
                } else {
                    if ($close < $finalLower) {
                        $direction = -1;
                        $supertrend = $finalUpper;
                    } else {
                        $direction = 1;
                        $supertrend = $finalLower;
                    }
                }
            }

            $results[] = [
                'time' => $time,
                'supertrend' => round($supertrend, 4),
                'direction' => $direction,
                'final_upper' => round($finalUpper, 4),
                'final_lower' => round($finalLower, 4),
            ];

            $prevFinalUpper = $finalUpper;
            $prevFinalLower = $finalLower;
            $prevSupertrend = $supertrend;
            $prevDirection = $direction;
        }

        return $results;
    }
}
