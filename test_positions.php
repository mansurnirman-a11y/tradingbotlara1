<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach(\App\Models\BrokerAccount::all() as $b) {
    echo "Broker: " . $b->id . " - " . $b->exchange . "\n";
    $s = new \App\Services\ExchangeService($b);
    try {
        $positions = $s->getClient()->fetchPositions(['BTC/USD']);
        echo json_encode($positions) . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
