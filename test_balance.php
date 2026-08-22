<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Assuming the user's latest account is ID 1 or something. Let's find the Delta one.
    $account = App\Models\BrokerAccount::where('broker', 'delta_india')->latest()->first();
    if (!$account) {
        $account = App\Models\BrokerAccount::latest()->first();
    }
    
    if (!$account) {
        echo "No broker account found.";
        exit;
    }
    
    echo "Testing account ID: {$account->id} ({$account->broker})\n";
    $client = new \ccxt\delta([
        'apiKey' => $account->api_key,
        'secret' => $account->api_secret,
        'urls' => [
            'api' => [
                'public' => 'https://api.india.delta.exchange',
                'private' => 'https://api.india.delta.exchange',
            ]
        ],
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]
    ]);
    
    $bal = $client->fetch_balance();
    print_r($bal);
    
    // Check for USDT specifically
    if (isset($bal['USDT'])) {
        print_r($bal['USDT']);
    } else {
        echo "No USDT key found in balance response.\n";
    }
    
    // Check total
    if (isset($bal['total'])) {
        print_r($bal['total']);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
