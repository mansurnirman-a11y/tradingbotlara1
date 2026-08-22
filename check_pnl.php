<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$positions = App\Models\Position::where('status', 'CLOSED')->get();
foreach ($positions as $p) {
    echo "ID: " . $p->id . " | PNL: " . $p->realized_pnl . "\n";
}
