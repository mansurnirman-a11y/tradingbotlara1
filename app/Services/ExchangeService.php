<?php

namespace App\Services;

use App\Models\BrokerAccount;
use Exception;

class ExchangeService
{
    protected $client;
    protected $isMetaApi = false;
    protected $isCustomApi = false;

    public function __construct(BrokerAccount $account)
    {
        if (in_array($account->broker, ['mt4', 'mt5'])) {
            // Route to MetaApi Bridge for MetaTrader
            $this->client = new MetaApiBridgeService($account);
            $this->isMetaApi = true;
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

    public function fetchOHLCV(string $symbol, string $timeframe = '15m', int $limit = 100)
    {
        if ($this->isMetaApi || $this->isCustomApi) {
            return $this->client->fetchOHLCV($symbol, $timeframe, $limit);
        }

        return $this->client->fetch_ohlcv($symbol, $timeframe, null, $limit);
    }

    public function createMarketOrder(string $symbol, string $side, float $amount)
    {
        if ($this->isMetaApi || $this->isCustomApi) {
            return $this->client->createMarketOrder($symbol, $side, $amount);
        }

        // CCXT implementation: Real Market Order
        return $this->client->create_market_order($symbol, $side, $amount);
    }

    public function formatAmount(string $symbol, float $amount)
    {
        if ($this->isMetaApi || $this->isCustomApi) {
            return round($amount, 4); // Default to 4 decimals for custom/localhost lots
        }
        
        try {
            if (!$this->client->markets) {
                $this->client->load_markets();
            }
            return $this->client->amount_to_precision($symbol, $amount);
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
            $ticker = $this->client->fetch_ticker($symbol);
            return $ticker['last'] ?? null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Fetch Ticker Error for {$symbol}: " . $e->getMessage());
            return null;
        }
    }

    public function getMarketInfo(string $symbol)
    {
        try {
            if ($this->isMetaApi || $this->isCustomApi) return null;
            if (!$this->client->markets) {
                $this->client->load_markets();
            }
            return $this->client->market($symbol);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getContractSize(string $symbol)
    {
        // Use the underlying exchange ID for caching so different accounts on same exchange share cache
        $exchangeId = $this->isMetaApi ? 'metaapi' : ($this->isCustomApi ? 'customapi' : $this->client->id);
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

    public function fetchBalance()
    {
        try {
            if ($this->isMetaApi) {
                // MetaApi implementation if supported, else return empty
                return ['USDT' => ['free' => 0, 'used' => 0, 'total' => 0]];
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
}
