<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$accounts = \App\Models\BrokerAccount::where('broker', 'delta_india')->get();
foreach ($accounts as $account) {
    echo "\n=== Testing Account {$account->id} ({$account->account_label}) ===\n";
    $exchange = new \App\Services\ExchangeService($account);
    try {
        $balance = $exchange->getClient()->fetch_balance();
        echo "SUCCESS! Keys: " . implode(', ', array_keys($balance)) . "\n";
    } catch (\Exception $e) {
        echo "Exception (" . get_class($e) . "): " . $e->getMessage() . "\n";
    }
}
