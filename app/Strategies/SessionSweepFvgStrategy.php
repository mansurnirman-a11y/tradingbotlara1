<?php

namespace App\Strategies;

class SessionSweepFvgStrategy implements StrategyInterface
{
    /**
     * Evaluate the given OHLCV candles and return a signal.
     * 
     * @param array $candles [ [timestamp, open, high, low, close, volume], ... ]
     * @param array $parameters User defined parameters (e.g. ['swing_lookback' => 5, 'rr_ratio' => 2.5])
     * @return string 'BUY', 'SELL', or 'HOLD'
     */
    public function evaluate(array $candles, array $parameters): string
    {
        $count = count($candles);
        $lookback = (int)($parameters['swing_lookback'] ?? 5);

        if ($count < ($lookback * 3) + 10) {
            return 'HOLD';
        }

        // Run full state simulation over candles
        $analysis = $this->analyzeSeries($candles, $parameters);

        if (empty($analysis)) {
            return 'HOLD';
        }

        // Evaluate confirmed closed candle (count - 2) or live confirmed bar
        $idxConfirmed = $count - 2;
        if (isset($analysis[$idxConfirmed])) {
            $item = $analysis[$idxConfirmed];
            if ($item['signal'] === 'BUY') {
                return 'BUY';
            }
            if ($item['signal'] === 'SELL') {
                return 'SELL';
            }
        }

        return 'HOLD';
    }

    /**
     * Get the historical indicator data for visualization and chart plotting.
     */
    public function getChartData(array $candles, array $parameters): array
    {
        $analysis = $this->analyzeSeries($candles, $parameters);

        $lineData = [];
        $signals = [];

        foreach ($analysis as $item) {
            $time = (int)floor($item['time'] / 1000);

            if ($item['asia_high'] !== null) {
                $lineData[] = [
                    'time' => $time,
                    'value' => $item['asia_high'],
                    'color' => 'rgba(255, 61, 0, 0.7)',
                    'title' => 'Asian High'
                ];
            }

            if ($item['signal'] === 'BUY') {
                $signals[] = [
                    'time' => $time,
                    'position' => 'belowBar',
                    'color' => '#00e676',
                    'shape' => 'arrowUp',
                    'text' => 'BUY (Sweep + FVG)'
                ];
            } elseif ($item['signal'] === 'SELL') {
                $signals[] = [
                    'time' => $time,
                    'position' => 'aboveBar',
                    'color' => '#ff3d00',
                    'shape' => 'arrowDown',
                    'text' => 'SELL (Sweep + FVG)'
                ];
            }
        }

        return [
            'type' => 'SessionSweepFVG',
            'data' => $lineData,
            'signals' => $signals
        ];
    }

    /**
     * Complete calculation of Asian Session Range, Liquidity Sweeps, Pivots (MSS), and FVG.
     */
    private function analyzeSeries(array $candles, array $parameters): array
    {
        $lookback = (int)($parameters['swing_lookback'] ?? 5);
        if ($lookback < 2) $lookback = 2;
        if ($lookback > 20) $lookback = 20;

        $count = count($candles);
        if ($count < ($lookback * 2) + 5) {
            return [];
        }

        $results = [];

        // Daily Asian session tracking
        $dayAsiaHigh = [];
        $dayAsiaLow = [];
        $daySweptLow = [];
        $daySweptHigh = [];

        // 1. First Pass: Compute Asian High/Low per date (UTC)
        for ($i = 0; $i < $count; $i++) {
            $t = (int)($candles[$i][0] / 1000);
            $date = gmdate('Y-m-d', $t);
            $hour = (int)gmdate('H', $t);
            $high = (float)$candles[$i][2];
            $low = (float)$candles[$i][3];

            // Asian Session: 00:00 to 06:00 UTC (hour 0, 1, 2, 3, 4, 5)
            if ($hour >= 0 && $hour < 6) {
                $dayAsiaHigh[$date] = max($dayAsiaHigh[$date] ?? -INF, $high);
                $dayAsiaLow[$date] = min($dayAsiaLow[$date] ?? INF, $low);
            }
        }

        // 2. Track Pivot Highs and Pivot Lows across the series
        $pivotHighs = array_fill(0, $count, null);
        $pivotLows = array_fill(0, $count, null);

        for ($i = $lookback; $i < $count - $lookback; $i++) {
            $currHigh = (float)$candles[$i][2];
            $currLow = (float)$candles[$i][3];

            $isHigh = true;
            $isLow = true;

            for ($j = 1; $j <= $lookback; $j++) {
                if ($candles[$i - $j][2] >= $currHigh || $candles[$i + $j][2] > $currHigh) {
                    $isHigh = false;
                }
                if ($candles[$i - $j][3] <= $currLow || $candles[$i + $j][3] < $currLow) {
                    $isLow = false;
                }
            }

            if ($isHigh) {
                // Pivot is fully confirmed at candle index (i + lookback)
                $pivotHighs[$i + $lookback] = $currHigh;
            }
            if ($isLow) {
                // Pivot is fully confirmed at candle index (i + lookback)
                $pivotLows[$i + $lookback] = $currLow;
            }
        }

        $lastSwingHigh = null;
        $lastSwingLow = null;

        // 3. Second Pass: Evaluate Sweeps, MSS, FVG and Triggers
        for ($i = 0; $i < $count; $i++) {
            $t = (int)($candles[$i][0] / 1000);
            $date = gmdate('Y-m-d', $t);
            $hour = (int)gmdate('H', $t);
            $high = (float)$candles[$i][2];
            $low = (float)$candles[$i][3];
            $close = (float)$candles[$i][4];

            $prevClose = ($i > 0) ? (float)$candles[$i - 1][4] : $close;

            // Update confirmed swing levels
            if ($pivotHighs[$i] !== null) {
                $lastSwingHigh = $pivotHighs[$i];
            }
            if ($pivotLows[$i] !== null) {
                $lastSwingLow = $pivotLows[$i];
            }

            // Asian Session values for this day
            $asiaHigh = isset($dayAsiaHigh[$date]) && $dayAsiaHigh[$date] > -INF ? $dayAsiaHigh[$date] : null;
            $asiaLow = isset($dayAsiaLow[$date]) && $dayAsiaLow[$date] < INF ? $dayAsiaLow[$date] : null;

            // Execution Session: 07:00 to 16:00 UTC
            $inTradeSession = ($hour >= 7 && $hour <= 16);

            // Check Liquidity Sweeps during Trade Session
            if ($inTradeSession && $asiaLow !== null && $low < $asiaLow) {
                $daySweptLow[$date] = true;
            }
            if ($inTradeSession && $asiaHigh !== null && $high > $asiaHigh) {
                $daySweptHigh[$date] = true;
            }

            $sweptAsiaLow = $daySweptLow[$date] ?? false;
            $sweptAsiaHigh = $daySweptHigh[$date] ?? false;

            // Market Structure Shift (MSS)
            $bullishMss = false;
            $bearishMss = false;

            if ($lastSwingHigh !== null && $close > $lastSwingHigh && $prevClose <= $lastSwingHigh) {
                $bullishMss = true;
            }
            if ($lastSwingLow !== null && $close < $lastSwingLow && $prevClose >= $lastSwingLow) {
                $bearishMss = true;
            }

            // Fair Value Gap (FVG) Check
            $bullishFvg = false;
            $bearishFvg = false;

            if ($i >= 2) {
                $prev2High = (float)$candles[$i - 2][2];
                $prev2Low = (float)$candles[$i - 2][3];
                $prev1Close = (float)$candles[$i - 1][4];

                if ($low > $prev2High && $prev1Close > $prev2High) {
                    $bullishFvg = true;
                }
                if ($high < $prev2Low && $prev1Close < $prev2Low) {
                    $bearishFvg = true;
                }
            }

            // Check if FVG happened on current or previous 2 bars (for realistic confluence)
            $recentBullishFvg = $bullishFvg;
            $recentBearishFvg = $bearishFvg;

            if (!$recentBullishFvg && $i >= 3) {
                $p2H = (float)$candles[$i - 3][2];
                $p1C = (float)$candles[$i - 2][4];
                $pL = (float)$candles[$i - 1][3];
                if ($pL > $p2H && $p1C > $p2H) {
                    $recentBullishFvg = true;
                }
            }
            if (!$recentBearishFvg && $i >= 3) {
                $p2L = (float)$candles[$i - 3][3];
                $p1C = (float)$candles[$i - 2][4];
                $pH = (float)$candles[$i - 1][2];
                if ($pH < $p2L && $p1C < $p2L) {
                    $recentBearishFvg = true;
                }
            }

            // Trigger Signal
            $signal = 'HOLD';

            if ($inTradeSession && $sweptAsiaLow && $bullishMss && $recentBullishFvg) {
                $signal = 'BUY';
                $daySweptLow[$date] = false; // Reset sweep after entry trigger
            } elseif ($inTradeSession && $sweptAsiaHigh && $bearishMss && $recentBearishFvg) {
                $signal = 'SELL';
                $daySweptHigh[$date] = false; // Reset sweep after entry trigger
            }

            $results[$i] = [
                'time' => $candles[$i][0],
                'close' => $close,
                'asia_high' => $asiaHigh,
                'asia_low' => $asiaLow,
                'swept_low' => $sweptAsiaLow,
                'swept_high' => $sweptAsiaHigh,
                'last_swing_high' => $lastSwingHigh,
                'last_swing_low' => $lastSwingLow,
                'bullish_mss' => $bullishMss,
                'bearish_mss' => $bearishMss,
                'bullish_fvg' => $bullishFvg,
                'bearish_fvg' => $bearishFvg,
                'signal' => $signal,
            ];
        }

        return $results;
    }
}
