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
        if (!Schema::hasColumn('bot_instances', 'deleted_at')) {
            Schema::table('bot_instances', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('broker_accounts', 'deleted_at')) {
            Schema::table('broker_accounts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('bot_instances', 'deleted_at')) {
            Schema::table('bot_instances', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('broker_accounts', 'deleted_at')) {
            Schema::table('broker_accounts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
