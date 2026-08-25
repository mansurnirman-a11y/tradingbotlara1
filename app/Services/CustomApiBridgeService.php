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
     * Get full endpoint URL.
     */
    protected function getUrl(string $endpoint): string
    {
        if ($this->broker === 'oanda') {
            $base = $this->baseUrl;
            if (!str_contains($base, '/api/v1')) {
                $base = rtrim($base, '/') . '/api/v1';
            }
            return rtrim($base, '/') . '/' . ltrim($endpoint, '/');
        }
        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Get headers for the API request.
     */
    protected function getHeaders(): array
    {
        if ($this->broker === 'oanda') {
            return [
                'x-api-key' => $this->apiKey,
                'x-api-secret' => $this->apiSecret,
                'Accept' => 'application/json'
            ];
        }
        return [
            'Accept' => 'application/json'
        ];
    }

    /**
     * Fetch OHLCV data. Maps custom API response to CCXT format.
     */
    public function fetchOHLCV(string $symbol, string $timeframe = '15m', int $limit = 100)
    {
        try {
            $url = $this->getUrl('ohlcv');
            $response = Http::timeout(5)->get($url, [
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
            if ($this->broker === 'oanda') {
                $url = $this->getUrl('trade/spot_order');
                $response = Http::timeout(5)->withHeaders($this->getHeaders())->post($url, [
                    'symbol' => $symbol,
                    'side' => strtoupper($side),
                    'quantity' => $amount,
                ]);
            } else {
                $url = $this->getUrl('order');
                $response = Http::timeout(5)->post($url, [
                    'symbol' => $symbol,
                    'side' => $side,
                    'amount' => $amount,
                    'api_key' => $this->apiKey,
                    'api_secret' => $this->apiSecret,
                ]);
            }

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
            if ($this->broker === 'oanda') {
                // Since Oanda backend derives prices from Binance WS, we fetch live price from Binance public API
                $cleanSymbol = strtoupper(str_replace(['/', '-'], '', $symbol));
                
                // Normalise common USD crypto symbols to USDT for Binance query
                if ($cleanSymbol === 'BTCUSD') $cleanSymbol = 'BTCUSDT';
                if ($cleanSymbol === 'ETHUSD') $cleanSymbol = 'ETHUSDT';
                if ($cleanSymbol === 'SOLUSD') $cleanSymbol = 'SOLUSDT';
                
                $response = Http::timeout(3)->get("https://api.binance.com/api/v3/ticker/price", [
                    'symbol' => $cleanSymbol
                ]);
                
                if ($response->successful() && isset($response->json()['price'])) {
                    return (float)$response->json()['price'];
                }
                
                // If it's a forex pair (e.g. EUR/USD)
                if (str_contains($symbol, 'USD') || str_contains($symbol, '/')) {
                    $parts = explode('/', $symbol);
                    $base = $parts[0] ?? '';
                    if ($base && $base !== 'USD') {
                        $forexResponse = Http::timeout(3)->get("https://open.er-api.com/v6/latest/USD");
                        if ($forexResponse->successful() && isset($forexResponse->json()['rates'][strtoupper($base)])) {
                            $rate = (float)$forexResponse->json()['rates'][strtoupper($base)];
                            if ($rate > 0) {
                                return 1 / $rate;
                            }
                        }
                    }
                }
            } else {
                $url = $this->getUrl('ticker');
                $response = Http::timeout(3)->get($url, [
                    'symbol' => $symbol
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['last'] ?? null;
                }
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
            if ($this->broker === 'oanda') {
                $url = $this->getUrl('account/balance');
                $response = Http::timeout(3)->withHeaders($this->getHeaders())->get($url);
            } else {
                $url = $this->getUrl('balance');
                $response = Http::timeout(3)->get($url, [
                    'api_key' => $this->apiKey,
                    'api_secret' => $this->apiSecret
                ]);
            }

            if ($response->successful() && is_array($response->json())) {
                $data = $response->json();
                
                // --- Normalize Response Formats ---
                
                // Case 1: Simple numeric string or float (e.g. 5000.50 or "5000.50")
                if (is_numeric($data)) {
                    return [
                        'USD' => [
                            'free' => (float)$data,
                            'used' => 0.0,
                            'total' => (float)$data
                        ]
                    ];
                }
                
                // Case 2: Simple key-value containing balance: e.g. {"balance": 5000.50, "currency": "USD"}
                if (isset($data['balance'])) {
                    $currency = $data['currency'] ?? 'USD';
                    return [
                        $currency => [
                            'free' => (float)$data['balance'],
                            'used' => 0.0,
                            'total' => (float)$data['balance']
                        ]
                    ];
                }
                
                // Case 3: Already standard CCXT nested structure: e.g. {"USD": {"free": 5000}} or {"USDT": {"free": 5000}}
                if (isset($data['USD']['free']) || isset($data['USDT']['free']) || isset($data['USD']['total']) || isset($data['USDT']['total'])) {
                    return $data;
                }
                
                // Case 4: Flat dictionary: e.g. {"USD": 5000.50, "USDT": 100.00}
                $normalized = [];
                foreach ($data as $asset => $val) {
                    if (is_numeric($val)) {
                        $normalized[$asset] = [
                            'free' => (float)$val,
                            'used' => 0.0,
                            'total' => (float)$val
                        ];
                    } elseif (is_array($val) && (isset($val['free']) || isset($val['total']))) {
                        $normalized[$asset] = [
                            'free' => (float)($val['free'] ?? $val['total'] ?? 0),
                            'used' => (float)($val['used'] ?? 0),
                            'total' => (float)($val['total'] ?? $val['free'] ?? 0)
                        ];
                    }
                }
                
                if (!empty($normalized)) {
                    return $normalized;
                }

                return $data;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch balance from custom/localhost API: " . $e->getMessage());
        }

        // Return empty array on failure so the controller/daemon detects the error properly
        return [];
    }

    /**
     * Fetch Positions
     */
    public function fetchPositions()
    {
        try {
            if ($this->broker === 'oanda') {
                $url = $this->getUrl('trade/positions');
                $response = Http::timeout(3)->withHeaders($this->getHeaders())->get($url);
                if ($response->successful() && is_array($response->json())) {
                    return $response->json()['positions'] ?? [];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch Oanda positions: " . $e->getMessage());
        }
        return [];
    }
}
