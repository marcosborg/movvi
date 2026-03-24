<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('adjustments', 'affects_vehicle_profitability')) {
            return;
        }

        Schema::table('adjustments', function (Blueprint $table) {
            $table->boolean('affects_vehicle_profitability')
                ->default(false)
                ->after('fleet_management');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('adjustments', 'affects_vehicle_profitability')) {
            return;
        }

        Schema::table('adjustments', function (Blueprint $table) {
            $table->dropColumn('affects_vehicle_profitability');
        });
    }
};
