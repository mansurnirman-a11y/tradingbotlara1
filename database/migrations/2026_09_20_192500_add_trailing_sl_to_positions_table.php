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
        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'peak_price')) {
                $table->decimal('peak_price', 28, 10)->nullable()->after('entry_price');
            }
            if (!Schema::hasColumn('positions', 'trailing_sl')) {
                $table->decimal('trailing_sl', 28, 10)->nullable()->after('peak_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (Schema::hasColumn('positions', 'trailing_sl')) {
                $table->dropColumn('trailing_sl');
            }
            if (Schema::hasColumn('positions', 'peak_price')) {
                $table->dropColumn('peak_price');
            }
        });
    }
};
