<?php

require __DIR__ . '/vendor/autoload.php';
$client = new \ccxt\delta([
    'urls' => [
        'api' => [
            'public' => 'https://api.india.delta.exchange',
            'private' => 'https://api.india.delta.exchange',
        ]
    ],
    'headers' => ['User-Agent' => 'Mozilla/5.0']
]);

$client->load_markets();
$market = $client->market('BTC/USDT');

print_r([
    'contractSize' => $market['contractSize'] ?? null,
    'precision' => $market['precision'] ?? null,
    'limits' => $market['limits'] ?? null,
]);
