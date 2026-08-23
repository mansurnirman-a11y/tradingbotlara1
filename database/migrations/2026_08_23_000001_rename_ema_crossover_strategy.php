<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('strategies')
            ->where('class_name', 'App\Strategies\EmaCrossoverStrategy')
            ->update(['name' => 'EMA Crossover (High Fec)']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('strategies')
            ->where('class_name', 'App\Strategies\EmaCrossoverStrategy')
            ->update(['name' => 'EMA Crossover (Hacking System)']);
    }
};
