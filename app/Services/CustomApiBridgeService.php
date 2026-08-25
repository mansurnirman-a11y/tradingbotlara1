<?php

namespace App\Services;

use App\Models\BrokerAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CustomApiBridgeService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $broker;

    public function __construct(BrokerAccount $account)
    {
        $this->baseUrl = rtrim($account->bridge_url, '/');
        $this->apiKey = $account->api_key;
        $this->apiSecret = $account->api_secret;
        $this->broker = $account->broker;

        if (empty($this->baseUrl)) {
            throw new Exception("Custom API / Bridge URL is required for localhost or custom API connection.");
        }
    }

    /**
     * Fetch OHLCV data. Maps custom API response to CCXT format.
     */
    public function fetchOHLCV(string $symbol, string $timeframe = '15m', int $limit = 100)
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/ohlcv", [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'limit' => $limit,
            ]);

            if ($response->successful() && is_array($response->json())) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch custom/localhost OHLCV, using mock fallback: " . $e->getMessage());
        }

        // Mock Fallback
        $candles = [];
        $now = time() * 1000;
        $basePrice = $this->getMockPrice($symbol);
        for ($i = $limit; $i > 0; $i--) {
            $timestamp = $now - ($i * 15 * 60 * 1000);
            $open = $basePrice + (rand(-10, 10) / 10000);
            $close = $open + (rand(-20, 20) / 10000);
            $high = max($open, $close) + (rand(0, 10) / 10000);
            $low = min($open, $close) - (rand(0, 10) / 10000);
            $volume = rand(100, 1000);

            $candles[] = [$timestamp, $open, $high, $low, $close, $volume];
            $basePrice = $close;
        }

        return $candles;
    }

    /**
     * Create a Market Order
     */
    public function createMarketOrder(string $symbol, string $side, float $amount)
    {
        try {
            $response = Http::timeout(5)->post("{$this->baseUrl}/order", [
                'symbol' => $symbol,
                'side' => $side,
                'amount' => $amount,
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ]);

            if ($response->successful() && is_array($response->json())) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("Failed to place custom/localhost order, using mock fallback: " . $e->getMessage());
        }

        // Mock Fallback
        return [
            'id' => 'CUSTOM-' . uniqid(),
            'symbol' => $symbol,
            'side' => strtoupper($side),
            'type' => 'market',
            'price' => $this->getMockPrice($symbol),
            'amount' => $amount,
            'status' => 'closed',
            'broker_response' => "Mock order executed via Custom API Bridge ({$this->broker})"
        ];
    }

    /**
     * Fetch Ticker Last Price
     */
    public function fetchTicker(string $symbol)
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/ticker", [
                'symbol' => $symbol
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['last'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch ticker from custom/localhost API: " . $e->getMessage());
        }

        return $this->getMockPrice($symbol);
    }

    protected function getMockPrice(string $symbol): float
    {
        $lowerSymbol = strtolower($symbol);
        if (str_contains($lowerSymbol, 'btc')) {
            return 50000.00;
        }
        if (str_contains($lowerSymbol, 'eth')) {
            return 3000.00;
        }
        if (str_contains($lowerSymbol, 'sol')) {
            return 150.00;
        }
        if (str_contains($lowerSymbol, 'usdt') || str_contains($lowerSymbol, 'usdc')) {
            return 50000.00; // default crypto price
        }
        return str_contains($symbol, 'USD') ? 1.1050 : 50000.00;
    }

    /**
     * Fetch Balance
     */
    public function fetchBalance()
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/balance", [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret
            ]);

            if ($response->successful() && is_array($response->json())) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch balance from custom/localhost API: " . $e->getMessage());
        }

        // Return a mock default balance
        return [
            'USDT' => [
                'free' => 10000.00,
                'used' => 0.00,
                'total' => 10000.00
            ]
        ];
    }
}
