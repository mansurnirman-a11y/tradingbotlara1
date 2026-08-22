<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(3);
$bot = App\Models\BotInstance::where('user_id', 3)->first();

if (!$bot) {
    echo "No bot found for this user.";
    exit;
}

$trade = App\Models\Trade::create([
    'bot_instance_id' => $bot->id,
    'user_id' => $user->id,
    'order_id' => 'MOCK-' . uniqid(),
    'symbol' => $bot->symbol,
    'side' => 'BUY',
    'type' => 'MARKET',
    'price' => 54320.50,
    'quantity' => 0.05,
    'status' => 'FILLED',
    'executed_at' => now(),
]);

try {
    $user->notify(new App\Notifications\TradeExecuted($trade));
    echo "Real Notification dispatched to " . $user->email . "!";
} catch (\Exception $e) {
    echo "Failed: " . $e->getMessage();
}
