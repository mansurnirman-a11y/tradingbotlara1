<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bot = \App\Models\BotInstance::find(6);
$errorMessage = 'delta {"error":{"code":"insufficient_margin","context":{"margin_mode":"isolated","asset_symbol":"USD","available_balance":"42.860098836000000000","required_additional_balance":"10.13349927"}},"success":false}';

// This will queue the email or send it synchronously depending on MAIL_QUEUE
$bot->user->notify(new \App\Notifications\BotErrorNotification($bot, $errorMessage));

echo "Notification sent to {$bot->user->email}!\n";
