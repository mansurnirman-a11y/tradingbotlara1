<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\Mail::raw('Test Email from Capital First', function($msg) { 
        $msg->to('mali08612@gmail.com')->subject('SMTP Test Success');
    });
    echo "Mail Sent Successfully!";
} catch (\Exception $e) {
    echo "Mail Failed: " . $e->getMessage();
}
