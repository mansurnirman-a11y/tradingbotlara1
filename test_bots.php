<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach ([6, 7] as $botId) {
    $bot = \App\Models\BotInstance::find($botId);
    if (!$bot) continue;
    echo "Testing Bot {$bot->id} ({$bot->strategy_class})\n";
    $exchangeService = new \App\Services\ExchangeService($bot->brokerAccount);
    $candles = $exchangeService->fetchOHLCV($bot->symbol, $bot->timeframe, 100);
    $strategyClass = $bot->strategy_class;
    $strategy = new $strategyClass();
    $signal = $strategy->evaluate($candles, $bot->parameters ?? []);
    echo "Current Close: " . end($candles)[4] . "\n";
    echo "SIGNAL: " . $signal . "\n\n";
}
echo "Done!\n";
