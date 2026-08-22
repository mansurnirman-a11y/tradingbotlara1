<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bot = \App\Models\BotInstance::find(6);
$service = new \App\Services\ExchangeService($bot->brokerAccount);
$market = $service->getMarketInfo('BTC/USD');

if ($market === null) {
    echo "MARKET IS NULL\n";
} else {
    echo "Contract Size: " . ($market['contractSize'] ?? 'N/A') . "\n";
}
