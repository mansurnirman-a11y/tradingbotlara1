<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Legal / Policy Pages
Route::get('/disclaimer', function() {
    return view('pages.policy', [
        'title' => 'Disclaimer',
        'content' => '<p>Trading cryptocurrencies, forex, and other financial instruments involves significant risk of loss and is not suitable for every investor. The valuation of financial instruments may fluctuate, and, as a result, clients may lose more than their original investment.</p><p>Capital First provides algorithmic execution software and does not offer financial, investment, or legal advice. Past performance of any trading system or methodology is not necessarily indicative of future results.</p>'
    ]);
})->name('policy.disclaimer');

Route::get('/terms', function() {
    return view('pages.policy', [
        'title' => 'Terms and Conditions',
        'content' => '<p>By accessing and using Capital First, you agree to be bound by these Terms and Conditions. You must be at least 18 years of age to use this platform.</p><p>You are solely responsible for securing your API keys and ensuring that your exchange accounts are properly configured. Capital First is not liable for any losses incurred due to misconfiguration, API failures, or market volatility.</p>'
    ]);
})->name('policy.terms');

Route::get('/privacy', function() {
    return view('pages.policy', [
        'title' => 'Privacy Policy',
        'content' => '<p>Your privacy is important to us. We collect necessary information such as your email and encrypted API keys solely for the purpose of providing our automated trading services.</p><p>We do not sell, rent, or share your personal data with third parties. Your API keys are encrypted at rest using industry-standard AES-256 encryption.</p>'
    ]);
})->name('policy.privacy');

Route::get('/refund', function() {
    return view('pages.policy', [
        'title' => 'Refund Policy',
        'content' => '<p>Due to the digital nature of our algorithmic software and server resources allocated immediately upon subscription, all sales are final.</p><p>We do not offer refunds, partial or in full, after a subscription has been activated or a service has been utilized.</p>'
    ]);
})->name('policy.refund');

// Webhook Route (Public)
Route::post('/api/webhook/{key}', [App\Http\Controllers\WebhookController::class, 'handleTradingView'])->name('webhook.tradingview');

Route::middleware('guest')->group(function () {
    // User Portal
    Route::get('/login', [AuthController::class, 'showLogin'])->defaults('role', 'user')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->defaults('role', 'user');
    Route::get('/register', [AuthController::class, 'showRegister'])->defaults('role', 'user')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->defaults('role', 'user');

    // Admin Portal
    Route::prefix('admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->defaults('role', 'admin')->name('admin.login');
        Route::post('/login', [AuthController::class, 'login'])->defaults('role', 'admin');
        Route::get('/register', [AuthController::class, 'showRegister'])->defaults('role', 'admin')->name('admin.register');
        Route::post('/register', [AuthController::class, 'register'])->defaults('role', 'admin');
    });

    // Superadmin Portal
    Route::get('/superadminmansur/login', [AuthController::class, 'showLogin'])->defaults('role', 'superadmin')->name('superadmin.login');
    Route::post('/superadminmansur/login', [AuthController::class, 'login'])->defaults('role', 'superadmin');
    Route::get('/superadmin123456/register', [AuthController::class, 'showRegister'])->defaults('role', 'superadmin')->name('superadmin.register');
    Route::post('/superadmin123456/register', [AuthController::class, 'register'])->defaults('role', 'superadmin');
});

use App\Http\Controllers\BrokerAccountController;
use App\Http\Controllers\BotInstanceController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\DashboardController;

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Broker Management
    Route::get('/brokers', [BrokerAccountController::class, 'index'])->name('brokers.index');
    Route::post('/brokers', [BrokerAccountController::class, 'store'])->name('brokers.store');
    Route::get('/brokers/live-balances', [BrokerAccountController::class, 'liveBalances'])->name('brokers.live-balances');
    Route::delete('/brokers/{account}', [BrokerAccountController::class, 'destroy'])->name('brokers.destroy');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

    // AJAX Live Data Endpoint
    Route::get('/bots/live-data', [BotInstanceController::class, 'liveData'])->name('bots.live-data');
    Route::get('/bots/{bot}/chart-data', [BotInstanceController::class, 'chartData'])->name('bots.chart-data');

    Route::resource('bots', BotInstanceController::class)->except(['show', 'edit', 'update']);
    Route::post('bots/{bot}/toggle', [BotInstanceController::class, 'toggleStatus'])->name('bots.toggle');

    // Notifications
    Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    Route::get('/trades', [TradeController::class, 'index'])->name('trades.index');
    Route::post('/trades/live-pnl', [TradeController::class, 'getLivePnl'])->name('trades.live_pnl');
    Route::post('/trades/{position}/close', [TradeController::class, 'closePosition'])->name('trades.close');
});

use App\Http\Controllers\AdminController;

Route::middleware(['role:admin,superadmin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/killswitch', [AdminController::class, 'globalKillSwitch'])->name('admin.killswitch');
    Route::post('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');

    // Strategy Management Routes
    Route::get('/admin/strategies', [App\Http\Controllers\StrategyController::class, 'index'])->name('admin.strategies');
    Route::post('/admin/strategies', [App\Http\Controllers\StrategyController::class, 'store'])->name('admin.strategies.store');
    Route::post('/admin/strategies/{id}/toggle', [App\Http\Controllers\StrategyController::class, 'toggleActive'])->name('admin.strategies.toggle');
    Route::delete('/admin/strategies/{id}', [App\Http\Controllers\StrategyController::class, 'destroy'])->name('admin.strategies.destroy');

    // TV Import
    Route::get('/admin/import-history', [App\Http\Controllers\AdminController::class, 'showImport'])->name('admin.import');
    Route::post('/admin/import-history', [App\Http\Controllers\AdminController::class, 'processImport'])->name('admin.import.process');
});
