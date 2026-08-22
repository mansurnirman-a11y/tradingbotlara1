<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/api/test', 'GET');
$bot = \App\Models\BotInstance::find(5);

$controller = new \App\Http\Controllers\BotInstanceController();
// Mock Auth
\Illuminate\Support\Facades\Auth::loginUsingId($bot->user_id);
$response = $controller->chartData($bot);

echo $response->getContent();
