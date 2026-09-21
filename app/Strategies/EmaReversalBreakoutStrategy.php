<?php

namespace App\Strategies;

class EmaReversalBreakoutStrategy implements StrategyInterface
{
    /**
     * Evaluate the given OHLCV candles and return a signal.
     * 
     * @param array $candles [ [timestamp, open, high, low, close, volume], ... ]
     * @param array $parameters User defined parameters:
     *   - ema_period (int, default: 5)
     *   - min_body_pct (float, default: 20.0) Second candle body % of first candle
     *   - breakout_valid_candles (int, default: 3)
     *   - entry_buffer_points (float, default: 5.0 for BTCUSD, or dynamic)
     *   - allow_wick_touch (bool, default: true)
     * @return string 'BUY', 'SELL', or 'HOLD'
     */
    public function evaluate(array $candles, array $parameters): string
    {
        $count = count($candles);
        $emaPeriod = (int)($parameters['ema_period'] ?? 5);

        if ($count < $emaPeriod + 10) {
            return 'HOLD';
        }

        $analysis = $this->analyzeSeries($candles, $parameters);
        if (empty($analysis)) {
            return 'HOLD';
        }

        // Evaluate confirmed closed candle (count - 2) or live bar (count - 1)
        foreach ([$count - 2, $count - 1] as $idx) {
            if (isset($analysis[$idx])) {
                $item = $analysis[$idx];
                if ($item['signal'] === 'BUY' || $item['signal'] === 'SELL') {
                    $tpPoints = floatval($parameters['take_profit_points'] ?? 400.0);
                    $slPrice = isset($item['setup']['stop']) ? floatval($item['setup']['stop']) : null;
                    $entryLvl = isset($item['setup']['level']) ? floatval($item['setup']['level']) : floatval($candles[$idx][4]);
                    
                    $tpPrice = ($item['signal'] === 'BUY')
                        ? ($entryLvl + $tpPoints)
                        : ($entryLvl - $tpPoints);

                    return [
                        'signal' => $item['signal'],
                        'stop_loss' => $slPrice,
                        'take_profit' => $tpPrice,
                        'entry_level' => $entryLvl,
                    ];
                }
            }
        }

        return 'HOLD';
    }

    /**
     * Get indicator & visualization data for charts.
     */
    public function getChartData(array $candles, array $parameters): array
    {
        $analysis = $this->analyzeSeries($candles, $parameters);

        $emaData = [];
        $signals = [];

        foreach ($analysis as $item) {
            $time = (int)floor($item['time'] / 1000);

            if ($item['ema'] !== null) {
                $emaData[] = [
                    'time' => $time,
                    'value' => round($item['ema'], 2),
                ];
            }

            if ($item['signal'] === 'BUY') {
                $signals[] = [
                    'time' => $time,
                    'position' => 'belowBar',
                    'color' => '#00e676',
                    'shape' => 'triangleUp',
                    'text' => 'BUY (EMA Reversal)'
                ];
            } elseif ($item['signal'] === 'SELL') {
                $signals[] = [
                    'time' => $time,
                    'position' => 'aboveBar',
                    'color' => '#ff3d00',
                    'shape' => 'triangleDown',
                    'text' => 'SELL (EMA Reversal)'
                ];
            }
        }

        return [
            'type' => 'EMA_REVERSAL_BREAKOUT',
            'series' => [
                ['name' => 'EMA 5', 'color' => '#ff9800', 'data' => $emaData]
            ],
            'signals' => $signals
        ];
    }

    /**
     * Sequential simulation matching the exact Pine Script Two-Stage Setup logic.
     */
    public function analyzeSeries(array $candles, array $parameters): array
    {
        $emaPeriod = (int)($parameters['ema_period'] ?? 5);
        $minBodyPct = (float)($parameters['min_body_pct'] ?? 20.0);
        $breakoutValidCandles = (int)($parameters['breakout_valid_candles'] ?? 3);
        $entryBuffer = (float)($parameters['entry_buffer_points'] ?? 5.0);
        $allowWickTouch = (bool)($parameters['allow_wick_touch'] ?? true);

        $closes = array_column($candles, 4);
        $emas = $this->calculateEma($closes, $emaPeriod);

        $results = [];

        // Queues matching Pine script:
        // firstSetup = [ ['direction' => 1|-1, 'bar' => idx, 'body_size' => float] ]
        $firstSetups = [];
        // confirmedSetups = [ ['direction' => 1|-1, 'level' => float, 'stop' => float, 'created_bar' => idx, 'expiry_bar' => idx] ]
        $confirmedSetups = [];

        for ($i = 0; $i < count($candles); $i++) {
            $time = $candles[$i][0];
            $open = (float)$candles[$i][1];
            $high = (float)$candles[$i][2];
            $low = (float)$candles[$i][3];
            $close = (float)$candles[$i][4];

            $emaVal = $emas[$i] ?? null;

            $signal = 'HOLD';
            $triggeredSetup = null;

            if ($emaVal !== null) {
                $candleRange = $high - $low;
                $bodySize = abs($close - $open);
                $bodyHigh = max($open, $close);
                $bodyLow = min($open, $close);

                $bodyAboveEMA = $bodyLow > $emaVal;
                $bodyBelowEMA = $bodyHigh < $emaVal;
                $fullAboveEMA = $low > $emaVal;
                $fullBelowEMA = $high < $emaVal;

                $validAboveEMA = $allowWickTouch ? $bodyAboveEMA : $fullAboveEMA;
                $validBelowEMA = $allowWickTouch ? $bodyBelowEMA : $fullBelowEMA;

                $isGreen = $close > $open;
                $isRed = $close < $open;

                // -------------------------------------------------------------
                // 1. Check breakout on pending confirmed setups
                // -------------------------------------------------------------
                foreach ($confirmedSetups as $idx => $cs) {
                    if ($i > $cs['created_bar'] && $i <= $cs['expiry_bar']) {
                        if ($cs['direction'] === 1 && $high > ($cs['level'] + $entryBuffer)) {
                            $signal = 'BUY';
                            $triggeredSetup = $cs;
                            unset($confirmedSetups[$idx]);
                            break;
                        } elseif ($cs['direction'] === -1 && $low < ($cs['level'] - $entryBuffer)) {
                            $signal = 'SELL';
                            $triggeredSetup = $cs;
                            unset($confirmedSetups[$idx]);
                            break;
                        }
                    }
                }

                // Remove expired confirmed setups
                foreach ($confirmedSetups as $idx => $cs) {
                    if ($i > $cs['expiry_bar']) {
                        unset($confirmedSetups[$idx]);
                    }
                }
                $confirmedSetups = array_values($confirmedSetups);

                // -------------------------------------------------------------
                // 2. Check confirmation candle for existing firstSetups
                // -------------------------------------------------------------
                foreach ($firstSetups as $idx => $fs) {
                    if ($i === $fs['bar'] + 1) {
                        $requiredSecondBody = $fs['body_size'] * ($minBodyPct / 100.0);
                        $secondBodyValid = $bodySize >= $requiredSecondBody;

                        if ($fs['direction'] === 1 && $isGreen && $secondBodyValid) {
                            // BUY Confirmed Setup
                            $confirmedSetups[] = [
                                'direction' => 1,
                                'level' => $high,
                                'stop' => $low,
                                'created_bar' => $i,
                                'expiry_bar' => $i + $breakoutValidCandles,
                            ];
                        } elseif ($fs['direction'] === -1 && $isRed && $secondBodyValid) {
                            // SELL Confirmed Setup
                            $confirmedSetups[] = [
                                'direction' => -1,
                                'level' => $low,
                                'stop' => $high,
                                'created_bar' => $i,
                                'expiry_bar' => $i + $breakoutValidCandles,
                            ];
                        }
                    }

                    // Once checked on bar + 1, remove from firstSetups
                    if ($i >= $fs['bar'] + 1) {
                        unset($firstSetups[$idx]);
                    }
                }
                $firstSetups = array_values($firstSetups);

                // -------------------------------------------------------------
                // 3. Register current candle as new First-Stage BASE setup
                // -------------------------------------------------------------
                // BUY Setup: EMA ke niche + Red candle
                if ($validBelowEMA && $bodySize > 0 && $isRed) {
                    $firstSetups[] = [
                        'direction' => 1,
                        'bar' => $i,
                        'body_size' => $bodySize,
                    ];
                }

                // SELL Setup: EMA ke upar + Green candle
                if ($validAboveEMA && $bodySize > 0 && $isGreen) {
                    $firstSetups[] = [
                        'direction' => -1,
                        'bar' => $i,
                        'body_size' => $bodySize,
                    ];
                }
            }

            $results[$i] = [
                'time' => $time,
                'ema' => $emaVal,
                'signal' => $signal,
                'setup' => $triggeredSetup
            ];
        }

        return $results;
    }

    /**
     * Standard Exponential Moving Average calculation.
     */
    protected function calculateEma(array $values, int $period): array
    {
        $count = count($values);
        if ($count < $period) {
            return array_fill(0, $count, null);
        }

        $ema = array_fill(0, $count, null);
        $k = 2.0 / ($period + 1);

        // Initial SMA
        $sum = 0;
        for ($i = 0; $i < $period; $i++) {
            $sum += $values[$i];
        }
        $prevEma = $sum / $period;
        $ema[$period - 1] = $prevEma;

        for ($i = $period; $i < $count; $i++) {
            $currentEma = ($values[$i] * $k) + ($prevEma * (1 - $k));
            $ema[$i] = $currentEma;
            $prevEma = $currentEma;
        }

        return $ema;
    }
}
