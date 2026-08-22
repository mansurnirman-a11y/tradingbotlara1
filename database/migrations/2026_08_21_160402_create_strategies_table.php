<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('strategies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['internal', 'webhook'])->default('internal');
            $table->string('class_name')->nullable();
            $table->string('webhook_key')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default strategies
        DB::table('strategies')->insert([
            ['name' => 'RSI Reversal (Basic)', 'type' => 'internal', 'class_name' => 'App\Strategies\RsiStrategy', 'description' => 'Trades RSI overbought and oversold conditions.'],
            ['name' => 'MACD Momentum Crossover', 'type' => 'internal', 'class_name' => 'App\Strategies\MacdStrategy', 'description' => 'Trades based on MACD line crossing the signal line.'],
            ['name' => 'SMA Trend Crossover', 'type' => 'internal', 'class_name' => 'App\Strategies\SmaCrossoverStrategy', 'description' => 'Simple moving average crossover strategy.'],
            ['name' => 'Bollinger Scalper (High Freq)', 'type' => 'internal', 'class_name' => 'App\Strategies\BollingerScalpingStrategy', 'description' => 'Scalping inside bollinger bands.'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategies');
    }
};
