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
        DB::table('strategies')->insert([
            [
                'name' => 'EMA Crossover (Hacking System)',
                'description' => 'Exponential moving average crossover strategy targeting closed confirmation candles with no default SL/TP.',
                'type' => 'internal',
                'class_name' => 'App\Strategies\EmaCrossoverStrategy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('strategies')
            ->where('class_name', 'App\Strategies\EmaCrossoverStrategy')
            ->delete();
    }
};
