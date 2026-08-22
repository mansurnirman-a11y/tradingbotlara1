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
        Schema::table('bot_instances', function (Blueprint $table) {
            $table->foreignId('strategy_id')->nullable()->constrained('strategies')->nullOnDelete();
        });

        // Migrate existing bots to link with the newly created strategies
        $strategies = DB::table('strategies')->get();
        foreach ($strategies as $strat) {
            DB::table('bot_instances')
                ->where('strategy_class', $strat->class_name)
                ->update(['strategy_id' => $strat->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_instances', function (Blueprint $table) {
            $table->dropForeign(['strategy_id']);
            $table->dropColumn('strategy_id');
        });
    }
};
