<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::first();
if ($user) {
    $user->role = 'superadmin';
    $user->save();
    echo "User {$user->email} is now a superadmin.\n";
} else {
    echo "No users found in database.\n";
}
