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

    /**
     * Universal, fuzzy symbol matching across brokers and exchange naming standards.
     * Matches: BTC/USDT, BTCUSDT, BTCUSD_PERP, BTC-PERPETUAL, BTC/USD:BTC, BTCUSD, BTCUSD.p, etc.
     */
    public static function symbolsMatch(?string $symA, ?string $symB): bool
    {
        if (empty($symA) || empty($symB)) {
            return false;
        }

        if (strcasecmp($symA, $symB) === 0) {
            return true;
        }

        $cleaner = function (string $s): string {
            $s = strtoupper(trim($s));
            // Metal alias conversions
            $s = str_replace('GOLD', 'XAUUSD', $s);
            $s = str_replace('SILVER', 'XAGUSD', $s);
            if ($s === 'XAU' || $s === 'XAU/USD') $s = 'XAUUSD';
            if ($s === 'XAG' || $s === 'XAG/USD') $s = 'XAGUSD';

            // Remove common derivative suffixes and broker suffixes (e.g. .p, .raw, .pro, _perp, -perpetual)
            $s = preg_replace('/(\.P|\.M|\.RAW|\.PRO|\.C|\.R|_PERP|-PERPETUAL|PERP|SWAP|FUTURES|:.*)$/i', '', $s);
            // Remove non-alphanumeric characters
            $s = preg_replace('/[^A-Z0-9]/', '', $s);
            
            // Remove trailing single-character broker account type suffixes (e.g., BTCUSDm -> BTCUSD, EURUSDc -> EURUSD)
            if (preg_match('/^(BTC|ETH|SOL|BNB|XRP|DOGE|EUR|GBP|AUD|NZD|USD|JPY|CAD|CHF|XAU|XAG)(USD|USDT|EUR|JPY|GBP)([MCR])$/i', $s, $matches)) {
                $s = $matches[1] . $matches[2];
            }

            return $s;
        };

        $cleanA = $cleaner($symA);
        $cleanB = $cleaner($symB);

        if ($cleanA === $cleanB) {
            return true;
        }

        // Crypto USD vs USDT equivalence
        $toUsd = function(string $s): string {
            if (str_ends_with($s, 'USDT')) {
                return substr($s, 0, -4) . 'USD';
            }
            return $s;
        };

        if ($toUsd($cleanA) === $toUsd($cleanB)) {
            return true;
        }

        // Base asset extraction matching
        $extractBase = function(string $s): string {
            $known = ['BTC', 'ETH', 'SOL', 'BNB', 'XRP', 'DOGE', 'ADA', 'AVAX', 'LINK', 'MATIC', 'EUR', 'GBP', 'AUD', 'NZD', 'USD', 'JPY', 'CAD', 'CHF', 'XAU', 'XAG'];
            foreach ($known as $asset) {
                if (str_starts_with($s, $asset)) {
                    return $asset;
                }
            }
            return substr($s, 0, 3);
        };

        $baseA = $extractBase($cleanA);
        $baseB = $extractBase($cleanB);
        if (!empty($baseA) && !empty($baseB) && $baseA === $baseB) {
            $quoteA = substr($cleanA, strlen($baseA));
            $quoteB = substr($cleanB, strlen($baseB));
            if ($quoteA === $quoteB || (in_array($quoteA, ['USD', 'USDT', '']) && in_array($quoteB, ['USD', 'USDT', '']))) {
                return true;
            }
        }

        return false;
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
            try {
                // Pass reduceOnly for derivative exchanges if supported
                if ($this->account->broker === 'delta_india' || str_contains($this->account->broker, 'delta') || str_contains($this->account->broker, 'futures')) {
                    if (is_callable([$this->client, 'create_order'])) {
                        return $this->client->create_order($resolved, 'market', $closeSide, $amount, null, ['reduceOnly' => true]);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to normal market order
            }
            return $this->createMarketOrder($resolved, $closeSide, $amount);
        }

        try {
            $openPositions = $this->getOpenPositions();
            foreach ($openPositions as $p) {
                if (self::symbolsMatch($p['symbol'], $symbol)) {
                    $contracts = floatval($p['contracts'] ?? $p['quantity'] ?? 0);
                    if ($contracts > 0) {
                        $closeSide = $p['side'] === 'LONG' ? 'sell' : 'buy';
                        return $this->createMarketOrder($resolved, $closeSide, $contracts);
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

    public function getOpenPositions(): array
    {
        try {
            $rawPositions = [];
            if ($this->isMetaApi || $this->isCustomApi) {
                if (is_callable([$this->client, 'getOpenPositions'])) {
                    $rawPositions = $this->client->getOpenPositions();
                } elseif (is_callable([$this->client, 'fetch_positions'])) {
                    $rawPositions = $this->client->fetch_positions();
                } elseif (is_callable([$this->client, 'fetchPositions'])) {
                    $rawPositions = $this->client->fetchPositions();
                }
            } else {
                if (is_callable([$this->client, 'fetch_positions'])) {
                    $rawPositions = $this->client->fetch_positions();
                } elseif (is_callable([$this->client, 'fetchPositions'])) {
                    $rawPositions = $this->client->fetchPositions();
                }
            }

            if (!is_array($rawPositions)) {
                return [];
            }

            $standardized = [];
            foreach ($rawPositions as $pos) {
                $symbol = $pos['symbol'] ?? $pos['product_symbol'] ?? $pos['info']['product_symbol'] ?? $pos['info']['symbol'] ?? '';
                if (empty($symbol)) continue;

                $contracts = floatval($pos['contracts'] ?? $pos['size'] ?? $pos['amount'] ?? $pos['volume'] ?? 0);
                if (abs($contracts) <= 0) continue;

                // Determine Side robustly
                $rawSide = strtoupper($pos['side'] ?? $pos['type'] ?? $pos['info']['side'] ?? '');
                if ($rawSide === 'LONG' || $rawSide === 'BUY' || $rawSide === 'POSITION_TYPE_BUY') {
                    $side = 'LONG';
                } elseif ($rawSide === 'SHORT' || $rawSide === 'SELL' || $rawSide === 'POSITION_TYPE_SELL') {
                    $side = 'SHORT';
                } else {
                    $side = $contracts > 0 ? 'LONG' : 'SHORT';
                }

                $entryPrice = floatval($pos['entryPrice'] ?? $pos['entry_price'] ?? $pos['averageEntryPrice'] ?? $pos['openPrice'] ?? $pos['info']['entry_price'] ?? 0);
                $markPrice = floatval($pos['markPrice'] ?? $pos['mark_price'] ?? $pos['currentPrice'] ?? $pos['current_price'] ?? $pos['price_current'] ?? $entryPrice);
                $unrealizedPnl = isset($pos['unrealizedPnl'])
                    ? floatval($pos['unrealizedPnl'])
                    : (isset($pos['unrealized_pnl']) ? floatval($pos['unrealized_pnl']) : (isset($pos['profit']) ? floatval($pos['profit']) : (isset($pos['unrealizedProfit']) ? floatval($pos['unrealizedProfit']) : 0.0)));

                $ticket = $pos['ticket'] ?? $pos['id'] ?? $pos['info']['id'] ?? $pos['info']['position_id'] ?? null;

                $standardized[] = [
                    'id' => $ticket,
                    'ticket' => $ticket,
                    'symbol' => $symbol,
                    'side' => $side,
                    'contracts' => abs($contracts),
                    'quantity' => abs($contracts),
                    'entry_price' => $entryPrice,
                    'current_price' => $markPrice,
                    'mark_price' => $markPrice,
                    'unrealized_pnl' => $unrealizedPnl,
                    'raw' => $pos,
                ];
            }

            return $standardized;
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
        $details = $this->fetchBalanceDetails();
        return (float) $details['free'];
    }

    public function getAccountCurrency(): string
    {
        $details = $this->fetchBalanceDetails();
        return $details['currency'] ?? 'USD';
    }

    public function fetchBalanceDetails(): array
    {
        $default = [
            'free' => 0.0,
            'total' => 0.0,
            'used' => 0.0,
            'equity' => 0.0,
            'currency' => 'USD',
            'formatted' => '0.00 USD'
        ];

        try {
            $data = $this->fetchBalance();
            if (empty($data) || !is_array($data)) {
                return $default;
            }

            // 1. Direct MT5 or MetaApi structure
            if (isset($data['balance']) || isset($data['equity']) || isset($data['free_margin'])) {
                $currency = $data['currency'] ?? 'USD';
                $balance = floatval($data['balance'] ?? 0);
                $equity = floatval($data['equity'] ?? $balance);
                $free = floatval($data['free_margin'] ?? $data['free'][$currency] ?? $equity);
                return [
                    'free' => $free > 0 ? $free : $equity,
                    'total' => $balance > 0 ? $balance : $equity,
                    'used' => max(0, $balance - $free),
                    'equity' => $equity,
                    'currency' => $currency,
                    'formatted' => number_format($free > 0 ? $free : $equity, 2) . ' ' . $currency
                ];
            }

            // 2. CCXT / Broker Balance Parsing
            $bestCurrency = 'USD';
            $bestFree = 0.0;
            $bestTotal = 0.0;

            // Priority order for fiat / stable currencies
            $priorityCurrencies = ['USDT', 'INR', 'USD', 'USDC', 'BUSD', 'EUR', 'GBP', 'BTC', 'ETH'];

            // Check standard CCXT currency dictionary
            foreach ($priorityCurrencies as $curr) {
                if (isset($data[$curr]) && is_array($data[$curr])) {
                    $free = floatval($data[$curr]['free'] ?? 0);
                    $total = floatval($data[$curr]['total'] ?? $free);
                    if ($free > 0 || $total > 0) {
                        $bestCurrency = $curr;
                        $bestFree = $free;
                        $bestTotal = $total;
                        break;
                    }
                }
            }

            // If nothing found in priority, check any currency with positive balance in $data['free'] or $data['total']
            if ($bestFree <= 0 && $bestTotal <= 0) {
                if (isset($data['free']) && is_array($data['free'])) {
                    foreach ($data['free'] as $curr => $val) {
                        if (is_numeric($val) && floatval($val) > 0) {
                            $bestCurrency = $curr;
                            $bestFree = floatval($val);
                            $bestTotal = floatval($data['total'][$curr] ?? $val);
                            break;
                        }
                    }
                }
            }

            // Check Delta Exchange info payload
            if ($bestFree <= 0 && $bestTotal <= 0 && isset($data['info']) && is_array($data['info'])) {
                $info = $data['info'];
                // Delta India wallet array: info['result'] or info['wallets'] or direct keys
                $wallets = $info['result'] ?? $info['wallets'] ?? (isset($info[0]) ? $info : []);
                if (is_array($wallets)) {
                    foreach ($wallets as $w) {
                        if (is_array($w)) {
                            $wBal = floatval($w['available_balance'] ?? $w['cash_balance'] ?? $w['balance'] ?? 0);
                            $wAsset = $w['asset_symbol'] ?? $w['currency'] ?? $w['symbol'] ?? 'INR';
                            if ($wBal > 0) {
                                $bestCurrency = $wAsset;
                                $bestFree = $wBal;
                                $bestTotal = floatval($w['balance'] ?? $wBal);
                                break;
                            }
                        }
                    }
                }

                // Flat info keys
                if ($bestFree <= 0) {
                    $directBal = floatval($info['cash_balance'] ?? $info['available_balance'] ?? $info['portfolio_value'] ?? $info['wallet_balance'] ?? 0);
                    if ($directBal > 0) {
                        $bestFree = $directBal;
                        $bestTotal = $directBal;
                        $bestCurrency = ($this->account && $this->account->broker === 'delta_india') ? 'INR' : 'USD';
                    }
                }
            }

            // Fallback to USDT / USD / INR key even if 0
            if ($bestFree <= 0 && $bestTotal <= 0) {
                if (isset($data['USDT']['free'])) {
                    $bestCurrency = 'USDT';
                    $bestFree = floatval($data['USDT']['free']);
                    $bestTotal = floatval($data['USDT']['total'] ?? $bestFree);
                } elseif (isset($data['USD']['free'])) {
                    $bestCurrency = 'USD';
                    $bestFree = floatval($data['USD']['free']);
                    $bestTotal = floatval($data['USD']['total'] ?? $bestFree);
                } elseif (isset($data['INR']['free'])) {
                    $bestCurrency = 'INR';
                    $bestFree = floatval($data['INR']['free']);
                    $bestTotal = floatval($data['INR']['total'] ?? $bestFree);
                }
            }

            return [
                'free' => $bestFree,
                'total' => $bestTotal > 0 ? $bestTotal : $bestFree,
                'used' => max(0, $bestTotal - $bestFree),
                'equity' => $bestTotal > 0 ? $bestTotal : $bestFree,
                'currency' => $bestCurrency,
                'formatted' => number_format($bestFree, 2) . ' ' . $bestCurrency
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("ExchangeService fetchBalanceDetails error: " . $e->getMessage());
            return $default;
        }
    }
}
