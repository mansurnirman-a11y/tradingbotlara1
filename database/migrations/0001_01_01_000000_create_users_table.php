<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 100);
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['superadmin', 'admin', 'user'])->default('user')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Broker Accounts (Encrypted API Credentials)
        Schema::create('broker_accounts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('broker', ['binance', 'delta_india', 'mt4', 'mt5'])->index();
            $table->string('account_label', 100);
            $table->text('api_key')->nullable();       // Encrypted via Crypt::encryptString()
            $table->text('api_secret')->nullable();    // Encrypted via Crypt::encryptString()
            $table->string('bridge_url', 255)->nullable(); // MT4/MT5 Python/FastAPI VPS endpoint
            $table->string('meta_account_id', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'broker', 'is_active']);
        });

        // 3. Bot Instances (Configuration & Strategy State)
        Schema::create('bot_instances', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('broker_account_id')->constrained('broker_accounts')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('symbol', 30)->index(); // e.g. "BTCUSDT", "EURUSD"
            $table->string('strategy_class', 150); // FQCN: App\Strategies\RsiStrategy
            $table->string('timeframe', 10)->default('15m');
            $table->decimal('allocated_capital', 28, 10);
            $table->decimal('max_drawdown_pct', 5, 2)->default(5.00); // Risk management kill-switch
            $table->json('parameters')->nullable(); // MySQL Native JSON for strategy parameters
            $table->enum('status', ['running', 'paused', 'stopped', 'error'])->default('paused')->index();
            $table->timestamps();
        });

        // 4. Trades & Order Executions
        Schema::create('trades', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('bot_instance_id')->constrained('bot_instances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('order_id', 100)->index();
            $table->string('symbol', 30)->index();
            $table->enum('side', ['BUY', 'SELL'])->index();
            $table->enum('type', ['MARKET', 'LIMIT']);
            $table->decimal('price', 28, 10);
            $table->decimal('quantity', 28, 10);
            $table->decimal('fee_paid', 28, 10)->default(0);
            $table->decimal('realized_pnl', 28, 10)->nullable();
            $table->enum('status', ['OPEN', 'FILLED', 'CANCELLED', 'REJECTED'])->index();
            $table->timestamp('executed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['bot_instance_id', 'status', 'created_at']);
        });

        // 5. Audit Logs (MySQL JSON Payload Storage)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index(); // e.g. 'bot.force_stop', 'role.updated'
            $table->string('resource_type', 100)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('payload')->nullable(); // Changes diff, metadata
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('trades');
        Schema::dropIfExists('bot_instances');
        Schema::dropIfExists('broker_accounts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
