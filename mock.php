<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bot = App\Models\BotInstance::first();

if ($bot) {
    App\Models\Position::create([
        'bot_instance_id' => $bot->id,
        'user_id' => $bot->user_id,
        'symbol' => $bot->symbol,
        'side' => 'LONG',
        'quantity' => 0.5,
        'entry_price' => 1.1050,
        'exit_price' => 1.1150,
        'realized_pnl' => 50.00,
        'status' => 'CLOSED',
        'opened_at' => now()->subMinutes(30),
        'closed_at' => now()
    ]);
    
    App\Models\Position::create([
        'bot_instance_id' => $bot->id,
        'user_id' => $bot->user_id,
        'symbol' => $bot->symbol,
        'side' => 'SHORT',
        'quantity' => 0.5,
        'entry_price' => 50000,
        'exit_price' => null,
        'realized_pnl' => null,
        'status' => 'OPEN',
        'opened_at' => now(),
    ]);

    echo "Positions Created";
} else {
    echo "No bots found.";
}
