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
        Schema::table('broker_accounts', function (Blueprint $table) {
            $table->string('broker', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broker_accounts', function (Blueprint $table) {
            $table->enum('broker', ['binance', 'delta_india', 'mt4', 'mt5'])->change();
        });
    }
};
