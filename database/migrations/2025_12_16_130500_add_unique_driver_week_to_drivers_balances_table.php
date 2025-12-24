<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('drivers_balances')) {
            return;
        }

        Schema::table('drivers_balances', function (Blueprint $table) {
            $table->unique(['driver_id', 'tvde_week_id'], 'drivers_balances_driver_id_tvde_week_id_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('drivers_balances')) {
            return;
        }

        Schema::table('drivers_balances', function (Blueprint $table) {
            $table->dropUnique('drivers_balances_driver_id_tvde_week_id_unique');
        });
    }
};
