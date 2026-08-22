<?php

namespace App\Strategies;

interface StrategyInterface
{
    /**
     * Evaluate the given OHLCV candles and return a signal.
     * 
     * @param array $candles [ [timestamp, open, high, low, close, volume], ... ]
     * @param array $parameters User defined parameters (e.g. ['rsi_period' => 14])
     * @return string 'BUY', 'SELL', or 'HOLD'
     */
    public function evaluate(array $candles, array $parameters): string;

    /**
     * Get the historical indicator data for visualization.
     * 
     * @param array $candles [ [timestamp, open, high, low, close, volume], ... ]
     * @param array $parameters User defined parameters
     * @return array Array containing the strategy type and time-series data
     */
    public function getChartData(array $candles, array $parameters): array;
}
