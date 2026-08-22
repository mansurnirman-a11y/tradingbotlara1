<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bot = \App\Models\BotInstance::find(6);
$service = new \App\Services\ExchangeService($bot->brokerAccount);
$service->getClient()->loadMarkets();
$market = $service->getClient()->market('BTC/USD');

print_r($market);
