<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bot = \App\Models\BotInstance::find(7);
if (!$bot) {
    echo "Bot 7 not found\n";
    exit;
}

echo "Testing Bot {$bot->id} ({$bot->strategy_class})\n";
$exchangeService = new \App\Services\ExchangeService($bot->brokerAccount);
$candles = $exchangeService->fetchOHLCV($bot->symbol, $bot->timeframe, 100);
$strategyClass = $bot->strategy_class;
$strategy = new $strategyClass();
$signal = $strategy->evaluate($candles, $bot->parameters ?? []);

echo "Current Close: " . end($candles)[4] . "\n";
echo "SIGNAL: " . $signal . "\n";

echo "Done testing!\n";
