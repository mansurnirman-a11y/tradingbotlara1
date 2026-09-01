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
        if (!Schema::hasColumn('broker_accounts', 'server_name')) {
            Schema::table('broker_accounts', function (Blueprint $table) {
                $table->string('server_name', 100)->nullable()->after('broker');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('broker_accounts', 'server_name')) {
            Schema::table('broker_accounts', function (Blueprint $table) {
                $table->dropColumn('server_name');
            });
        }
    }
};
