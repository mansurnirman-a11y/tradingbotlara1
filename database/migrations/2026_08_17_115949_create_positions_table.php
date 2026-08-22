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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_instance_id')->constrained('bot_instances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('symbol', 30)->index();
            $table->enum('side', ['LONG', 'SHORT'])->index(); // LONG = Buy first, SHORT = Sell first
            $table->decimal('quantity', 28, 10);
            $table->decimal('entry_price', 28, 10);
            $table->decimal('exit_price', 28, 10)->nullable();
            $table->decimal('realized_pnl', 28, 10)->nullable();
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN')->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
