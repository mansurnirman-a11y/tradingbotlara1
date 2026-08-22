<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new \App\Services\ExchangeService(\App\Models\BrokerAccount::find(2));
$order = $s->getClient()->fetchOrder('1478397267', 'BTC/USD');
echo json_encode($order);
