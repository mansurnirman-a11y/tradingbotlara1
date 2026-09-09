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
        $exists = DB::table('strategies')
            ->where('class_name', 'App\\Strategies\\SessionSweepFvgStrategy')
            ->exists();

        if (!$exists) {
            DB::table('strategies')->insert([
                'name' => 'BTC/Crypto Session Sweep & FVG (ICT)',
                'type' => 'internal',
                'class_name' => 'App\\Strategies\\SessionSweepFvgStrategy',
                'description' => 'Asian session liquidity sweep with MSS (Market Structure Shift) and Fair Value Gap confirmation (1:2.5 RR).',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('strategies')
            ->where('class_name', 'App\\Strategies\\SessionSweepFvgStrategy')
            ->delete();
    }
};
