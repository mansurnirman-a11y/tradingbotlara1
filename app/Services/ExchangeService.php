<?php

namespace App\Services;

use App\Models\BrokerAccount;
use Exception;

class ExchangeService
{
    protected $account;
    protected $client;
    protected $isMetaApi = false;
    protected $isCustomApi = false;

    public function __construct(BrokerAccount $account)
    {
        $this->account = $account;

        if (in_array($account->broker, ['mt4', 'mt5'])) {
            // If bridge_url is present, route to zero-cost local/VPS MT5 bridge
            if (!empty($account->bridge_url)) {
                $this->client = new CustomApiBridgeService($account);
                $this->isCustomApi = true;
            } elseif (!empty($account->meta_account_id)) {
                // Otherwise route to MetaApi Cloud Bridge
                $this->client = new MetaApiBridgeService($account);
                $this->isMetaApi = true;
            } else {
                // Default to zero-cost local/VPS tunnel MT5 bridge
                $account->bridge_url = 'http://127.0.0.1:5000';
                $this->client = new CustomApiBridgeService($account);
                $this->isCustomApi = true;
            }
        } elseif (in_array($account->broker, ['oanda', 'custom_api'])) {
            // Route to Custom API Bridge
            $this->client = new CustomApiBridgeService($account);
            $this->isCustomApi = true;
        } else {
            // Route to CCXT for Crypto Exchanges
            $brokerName = $account->broker;
            $options = [
                'apiKey' => $account->api_key, 
                'secret' => $account->api_secret, 
                'enableRateLimit' => true,
            ];

            if ($brokerName === 'delta_india') {
                $brokerName = 'delta';
                $options['urls'] = [
                    'api' => [
                        'public' => 'https://api.india.delta.exchange',
                        'private' => 'https://api.india.delta.exchange',
                    ]
                ];
                $options['headers'] = [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ];
            }

            $brokerClass = '\\ccxt\\' . $brokerName;

            if (!class_exists($brokerClass)) {
                throw new Exception("Broker [{$account->broker}] is not supported by CCXT.");
            }

            // Initialize the CCXT client
            $this->client = new $brokerClass($options);
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    public function resolveSymbol(string $symbol): string
    {
        if ($this->isMetaApi || $this->isCustomApi || in_array($this->account->broker ?? '', ['mt4', 'mt5', 'oanda'])) {
            return $symbol;
        }

        try {
            if (!$this->client->markets) {
                $this->client->load_markets();
            }

            if (isset($this->client->markets[$symbol])) {
                return $symbol;
            }

            $clean = strtoupper(str_replace(['/', '-', '_', ':', '.', ' '], '', $symbol));

            // Direct match by clean marketSymbol or ID
            foreach ($this->client->markets as $marketSymbol => $market) {
                $marketClean = strtoupper(str_replace(['/', '-', '_', ':', '.', ' '], '', $marketSymbol));
                $idClean = strtoupper(str_replace(['/', '-', '_', ':', '.', ' '], '', $market['id'] ?? ''));

                if ($marketClean === $clean || $idClean === $clean) {
                    return $marketSymbol;
                }
            }

            // If symbol ends with USDT, check if exchange uses USD (e.g. Delta Exchange: BTC/USD)
            if (str_ends_with($clean, 'USDT')) {
                $usdClean = substr($clean, 0, -4) . 'USD';
                foreach ($this->client->markets as $marketSymbol => $market) {
                    $marketClean = strtoupper(str_replace(['/', '-', '_', ':', '.', ' '], '', $marketSymbol));
                    $idClean = strtoupper(str_replace(['/', '-', '_', ':', '.', ' '], '', $market['id'] ?? ''));
                    if ($marketClean === $usdClean || $idClean === $usdClean) {
                        return $marketSymbol;
                    }
                }
            }

            // If symbol ends with USD, check if exchange uses USDT (e.g. Binance: BTC/USDT)
            if (str_ends_with($clean, 'USD')) {
                $usdtClean = substr($clean, 0, -3) . 'USDT';
                foreach ($this->client->markets as $marketSymbol => $market) {
                    $marketClean = strtoupper(str_replace(['/', '-', '_', ':', '.', ' '], '', $marketSymbol));
                    $idClean = strtoupper(str_replace(['/', '-', '_', ':', '.', ' '], '', $market['id'] ?? ''));
                    if ($marketClean === $usdtClean || $idClean === $usdtClean) {
                        return $marketSymbol;
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("ExchangeService resolveSymbol error for {$symbol}: " . $e->getMessage());
        }

        return $symbol;
    }

    public function fetchOHLCV(string $symbol, string $timeframe = '15m', int $limit = 100)
    {
        if ($this->isMetaApi || $this->isCustomApi) {
            return $this->client->fetchOHLCV($symbol, $timeframe, $limit);
        }

        $resolved = $this->resolveSymbol($symbol);
        return $this->client->fetch_ohlcv($resolved, $timeframe, null, $limit);
    }

    public function createMarketOrder(string $symbol, string $side, float $amount)
    {
        if ($this->isMetaApi || $this->isCustomApi) {
            return $this->client->createMarketOrder($symbol, $side, $amount);
        }

        // CCXT implementation: Real Market Order
        $resolved = $this->resolveSymbol($symbol);
        return $this->client->create_market_order($resolved, $side, $amount);
    }

    public function createOrder(string $symbol, string $type, string $side, float $amount, ?float $price = null)
    {
        if (strtolower($type) === 'market' || empty($price)) {
            return $this->createMarketOrder($symbol, $side, $amount);
        }

        if ($this->isMetaApi || $this->isCustomApi) {
            return $this->client->createMarketOrder($symbol, $side, $amount);
        }

        $resolved = $this->resolveSymbol($symbol);
        return $this->client->create_order($resolved, $type, $side, $amount, $price);
    }

    public function closePosition(string $symbol, ?float $amount = null, ?string $side = null)
    {
        if (($this->isMetaApi || $this->isCustomApi) && is_callable([$this->client, 'closePosition'])) {
            return $this->client->closePosition($symbol, $amount, $side);
        }

        $resolved = $this->resolveSymbol($symbol);

        if ($amount && $amount > 0 && $side) {
            $closeSide = strtoupper($side) === 'LONG' ? 'sell' : 'buy';
            return $this->createMarketOrder($resolved, $closeSide, $amount);
        }

        try {
            $client = $this->getClient();
            if (is_callable([$client, 'fetch_positions']) || is_callable([$client, 'fetchPositions'])) {
                $positions = is_callable([$client, 'fetch_positions']) ? $client->fetch_positions() : $client->fetchPositions();
                foreach ($positions as $p) {
                    $sym = $p['symbol'] ?? $p['product_symbol'] ?? '';
                    if (str_replace(['/', '-', ':'], '', $sym) === str_replace(['/', '-', ':'], '', $resolved)) {
                        $contracts = floatval($p['contracts'] ?? $p['amount'] ?? $p['size'] ?? 0);
                        if (abs($contracts) > 0) {
                            $closeSide = $contracts > 0 ? 'sell' : 'buy';
                            return $this->createMarketOrder($resolved, $closeSide, abs($contracts));
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("closePosition auto-detect failed for {$symbol}: " . $e->getMessage());
        }

        return null;
    }

    public function formatAmount(string $symbol, float $amount)
    {
        if ($this->isMetaApi || $this->isCustomApi || in_array($this->account->broker ?? '', ['mt4', 'mt5', 'oanda'])) {
            $formatted = round($amount, 2);
            if ($formatted < 0.01) {
                $formatted = 0.01;
            }
            return $formatted;
        }
        
        try {
            if (!$this->client->markets) {
                $this->client->load_markets();
            }
            $resolved = $this->resolveSymbol($symbol);
            return $this->client->amount_to_precision($resolved, $amount);
        } catch (\Exception $e) {
            return round($amount, 4); // Fallback
        }
    }

    public function fetchTicker(string $symbol)
    {
        try {
            if ($this->isMetaApi) {
                // MetaApi might not have a simple fetchTicker in our bridge, return null for now
                return null;
            }
            if ($this->isCustomApi) {
                return $this->client->fetchTicker($symbol);
            }
            $resolved = $this->resolveSymbol($symbol);
            $ticker = $this->client->fetch_ticker($resolved);
            return $ticker['last'] ?? null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Fetch Ticker Error for {$symbol}: " . $e->getMessage());
            return null;
        }
    }

    public function getMarketInfo(string $symbol)
    {
        try {
            if ($this->isMetaApi || $this->isCustomApi) {
                return null;
            }

            if (!$this->client->markets) {
                $this->client->load_markets();
            }
            $resolved = $this->resolveSymbol($symbol);
            return $this->client->market($resolved);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getContractSize(string $symbol)
    {
        $cleanSym = strtoupper(str_replace(['/', '-', ':', '.', ' '], '', $symbol));

        // For Forex / Metals / Commodities derivatives (MT4/MT5/Oanda)
        if ($this->isMetaApi || $this->isCustomApi || in_array($this->account->broker ?? '', ['mt4', 'mt5', 'oanda'])) {
            if (str_contains($cleanSym, 'XAU') || str_contains($cleanSym, 'GOLD')) {
                return 100.0; // 1 Standard Lot = 100 oz of Gold
            }
            if (str_contains($cleanSym, 'XAG') || str_contains($cleanSym, 'SILVER')) {
                return 5000.0; // 1 Standard Lot = 5000 oz of Silver
            }
            if (str_contains($cleanSym, 'BTC') || str_contains($cleanSym, 'ETH') || str_contains($cleanSym, 'SOL') || str_contains($cleanSym, 'BNB') || str_contains($cleanSym, 'DOGE')) {
                return 1.0; // Crypto base units
            }
            // Standard Forex currency pairs (EURUSD, GBPUSD, USDJPY, etc.)
            return 100000.0; // 1 Standard Lot = 100,000 units
        }

        // CCXT Crypto Exchange Contract Size
        $exchangeId = $this->client->id ?? 'ccxt';
        $cacheKey = "contract_size_{$exchangeId}_{$symbol}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($symbol) {
            $market = $this->getMarketInfo($symbol);
            if (!$market) return 1;

            // Try standard ccxt key first
            if (isset($market['contractSize'])) {
                return (float) $market['contractSize'];
            }

            // Try delta specific key
            if (isset($market['info']) && isset($market['info']['contract_value'])) {
                return (float) $market['info']['contract_value'];
            }

            return 1;
        });
    }

    public function getLeverage(string $symbol = '', ?float $override = null): float
    {
        if ($override !== null && $override > 0) {
            return (float) $override;
        }

        if ($this->account) {
            return $this->account->getEffectiveLeverage($override);
        }

        return 25.0;
    }

    public function getOpenPositions()
    {
        try {
            if ($this->isMetaApi || $this->isCustomApi) {
                if (is_callable([$this->client, 'getOpenPositions'])) {
                    return $this->client->getOpenPositions();
                }
                return [];
            }

            if (is_callable([$this->client, 'fetch_positions'])) {
                return $this->client->fetch_positions();
            }
            if (is_callable([$this->client, 'fetchPositions'])) {
                return $this->client->fetchPositions();
            }
            return [];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("ExchangeService getOpenPositions error: " . $e->getMessage());
            return [];
        }
    }

    public function fetchBalance()
    {
        try {
            if ($this->isMetaApi) {
                return $this->client->fetchBalance();
            }
            if ($this->isCustomApi) {
                return $this->client->fetchBalance();
            }

            $balance = $this->client->fetch_balance();
            return $balance; // CCXT returns an array where keys are currencies like 'USDT'
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Fetch Balance Error: " . $e->getMessage());
            return [];
        }
    }

    public function getAvailableBalance(): float
    {
        $balanceData = $this->fetchBalance();
        if (empty($balanceData)) {
            return 0.0;
        }

        // 1. Direct MT5 balance or equity
        if (isset($balanceData['balance']) && is_numeric($balanceData['balance']) && $balanceData['balance'] > 0) {
            return floatval($balanceData['balance']);
        }
        if (isset($balanceData['equity']) && is_numeric($balanceData['equity']) && $balanceData['equity'] > 0) {
            return floatval($balanceData['equity']);
        }
        if (isset($balanceData['free']['USD']) && is_numeric($balanceData['free']['USD'])) {
            return floatval($balanceData['free']['USD']);
        }
        if (isset($balanceData['total']['USD']) && is_numeric($balanceData['total']['USD'])) {
            return floatval($balanceData['total']['USD']);
        }

        // 2. CCXT standard format
        if (isset($balanceData['USDT']['free']) || isset($balanceData['USD']['free'])) {
            return floatval($balanceData['USDT']['free'] ?? 0) + floatval($balanceData['USD']['free'] ?? 0);
        }
        if (isset($balanceData['total']['USDT']) || isset($balanceData['total']['USD'])) {
            return floatval($balanceData['total']['USDT'] ?? 0) + floatval($balanceData['total']['USD'] ?? 0);
        }
        if (isset($balanceData['free']['USDT']) && is_numeric($balanceData['free']['USDT'])) {
            return floatval($balanceData['free']['USDT']);
        }

        // 3. Any positive currency
        foreach (['USDT', 'USD', 'INR', 'BTC', 'ETH'] as $curr) {
            if (isset($balanceData[$curr]['free']) && is_numeric($balanceData[$curr]['free']) && $balanceData[$curr]['free'] > 0) {
                return floatval($balanceData[$curr]['free']);
            }
        }

        return 0.0;
    }
}
