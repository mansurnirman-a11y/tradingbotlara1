<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bot = \App\Models\BotInstance::find(5);
echo "Bot Symbol: " . $bot->symbol . "\n";
$exchange = new \App\Services\ExchangeService($bot->brokerAccount);

try {
    $ohlcv = $exchange->fetchOHLCV($bot->symbol, $bot->timeframe, 150);
    echo "Count: " . count($ohlcv) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
