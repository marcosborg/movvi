<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_vehicle_evaluations', function (Blueprint $table) {
            $table->boolean('has_panel_warning')->default(false)->after('oil_level');
            $table->text('panel_warning_notes')->nullable()->after('has_panel_warning');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_vehicle_evaluations', function (Blueprint $table) {
            $table->dropColumn(['has_panel_warning', 'panel_warning_notes']);
        });
    }
};
