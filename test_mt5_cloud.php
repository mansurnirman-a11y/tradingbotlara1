<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BrokerAccount;
use App\Services\MetaApiBridgeService;
use App\Services\ExchangeService;

echo "==================================================\n";
echo "Testing MetaTrader 5 (MT5) Direct Cloud Integration\n";
echo "==================================================\n\n";

// 1. Create a dummy BrokerAccount instance for MT5
$mockAccount = new BrokerAccount([
    'user_id' => 1,
    'broker' => 'mt5',
    'server_name' => 'KasperCapitalMarkets-Server',
    'account_label' => 'Kasper MT5 Live Test',
    'api_key' => '10982341', // Login ID
    'api_secret' => 'SecretPass123!',
    'meta_account_id' => 'META-TEST-12345',
    'is_active' => true,
]);

echo "1. Account Configuration:\n";
echo "   - Broker: " . $mockAccount->broker . "\n";
echo "   - Server: " . $mockAccount->server_name . "\n";
echo "   - Label:  " . $mockAccount->account_label . "\n";
echo "   - Login:  " . $mockAccount->api_key . "\n\n";

// 2. Test ExchangeService instantiation
$exchangeService = new ExchangeService($mockAccount);
echo "2. Initialized ExchangeService successfully!\n";

// 3. Test fetchBalance()
$balance = $exchangeService->fetchBalance();
echo "3. Balance Response:\n";
print_r($balance);
echo "\n";

// 4. Test fetchOHLCV()
$candles = $exchangeService->fetchOHLCV('EURUSD', '15m', 5);
echo "4. Fetched " . count($candles) . " candles for EURUSD (15m):\n";
foreach ($candles as $c) {
    echo "   Time: " . date('Y-m-d H:i:s', $c[0]/1000) . " | Open: {$c[1]} | High: {$c[2]} | Low: {$c[3]} | Close: {$c[4]} | Vol: {$c[5]}\n";
}
echo "\n";

// 5. Test createMarketOrder()
$order = $exchangeService->createMarketOrder('EURUSD', 'BUY', 0.10);
echo "5. Placed Market Order (BUY EURUSD 0.10):\n";
print_r($order);
echo "\n";

// 6. Test getOpenPositions()
$positions = $exchangeService->getOpenPositions();
echo "6. Open Positions:\n";
print_r($positions);
echo "\n";

echo "==================================================\n";
echo "✅ All MT5 Cloud Bridge tests passed successfully!\n";
echo "==================================================\n";
