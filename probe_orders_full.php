<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$account = App\Models\BrokerAccount::where('broker', 'oanda')->first();
$ak = $account->api_key;
$sk = $account->api_secret;

$headers = [
    'x-api-key' => $ak,
    'x-api-secret' => $sk,
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
];

$urls = [
    // Standard v1 endpoints
    'https://oandaexchange.online/api/v1/trade/order',
    'https://oandaexchange.online/api/v1/trade/orders',
    'https://oandaexchange.online/api/v1/trade/futures',
    'https://oandaexchange.online/api/v1/trade/future',
    'https://oandaexchange.online/api/v1/trade/spot',
    'https://oandaexchange.online/api/v1/trade/spot_order',
    'https://oandaexchange.online/api/v1/trade/futures_order',
    'https://oandaexchange.online/api/v1/trade/position',
    'https://oandaexchange.online/api/v1/trade/positions',
    'https://oandaexchange.online/api/v1/trade',
    'https://oandaexchange.online/api/v1/order',
    'https://oandaexchange.online/api/v1/orders',
    'https://oandaexchange.online/api/v1/positions',
    'https://oandaexchange.online/api/v1/position',
    
    // Non-v1 endpoints
    'https://oandaexchange.online/api/trade/order',
    'https://oandaexchange.online/api/trade',
    'https://oandaexchange.online/api/trade/futures',
    'https://oandaexchange.online/api/trade/spot',
    'https://oandaexchange.online/api/orders',
    'https://oandaexchange.online/api/order',
    'https://oandaexchange.online/api/positions',
    'https://oandaexchange.online/api/position',
];

$payloads = [
    'standard' => [
        'symbol' => 'BTC/USDT',
        'type' => 'BUY',
        'side' => 'BUY',
        'amount' => 0.1,
        'quantity' => 0.1,
    ],
    'cleanSymbol' => [
        'symbol' => 'BTCUSDT',
        'type' => 'BUY',
        'side' => 'BUY',
        'amount' => 0.1,
        'quantity' => 0.1,
    ],
];

echo "Testing all endpoints with X-API-KEY / X-API-SECRET...\n";
foreach ($urls as $url) {
    foreach ($payloads as $pName => $payload) {
        try {
            $res = Illuminate\Support\Facades\Http::timeout(3)->withHeaders($headers)->post($url, $payload);
            if ($res->status() != 404) {
                echo "[POST] {$url} ({$pName}) -> Status: {$res->status()} | Body: {$res->body()}\n";
            }
        } catch (\Throwable $e) {}
    }
}
