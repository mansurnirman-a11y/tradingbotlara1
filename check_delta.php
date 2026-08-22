<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $client = new \ccxt\delta([
        'urls' => [
            'api' => [
                'public' => 'https://api.india.delta.exchange',
                'private' => 'https://api.india.delta.exchange',
            ]
        ]
    ]);
    print_r($client->urls);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
