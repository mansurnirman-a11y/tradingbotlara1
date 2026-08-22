<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\BrokerAccount;
use App\Models\BotInstance;
use App\Models\Trade;

$user = User::create([
    'name' => 'Admin User',
    'email' => 'admin' . time() . '@test.com',
    'password' => bcrypt('password'),
    'role' => 'superadmin',
]);

$broker = BrokerAccount::create([
    'user_id' => $user->id,
    'broker' => 'binance',
    'account_label' => 'Main Binance',
    'api_key' => 'TEST_KEY',
    'api_secret' => 'TEST_SECRET',
]);

$bot = BotInstance::create([
    'user_id' => $user->id,
    'broker_account_id' => $broker->id,
    'name' => 'BTC Scalper',
    'symbol' => 'BTCUSDT',
    'strategy_class' => 'App\Strategies\RsiStrategy',
    'allocated_capital' => 1.1234567890,
    'parameters' => ['rsi_period' => 14],
]);

$trade = Trade::create([
    'bot_instance_id' => $bot->id,
    'user_id' => $user->id,
    'order_id' => 'ORD-' . time(),
    'symbol' => 'BTCUSDT',
    'side' => 'BUY',
    'type' => 'MARKET',
    'price' => 59123.4567890123,
    'quantity' => 0.0012345678,
    'status' => 'FILLED',
]);

echo "Bot Capital inserted as: " . $bot->allocated_capital . "\n";
echo "Trade Price inserted as: " . $trade->price . "\n";
echo "Trade Quantity inserted as: " . $trade->quantity . "\n";

