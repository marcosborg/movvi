<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conta_azul_vehicle_revenue_exports', function (Blueprint $table) {
            $table->json('correction_history')->nullable()->after('acquittance_payload');
            $table->timestamp('last_corrected_at')->nullable()->after('exported_by');
            $table->unsignedBigInteger('last_corrected_by')->nullable()->after('last_corrected_at');
        });
    }

    public function down(): void
    {
        Schema::table('conta_azul_vehicle_revenue_exports', function (Blueprint $table) {
            $table->dropColumn(['correction_history', 'last_corrected_at', 'last_corrected_by']);
        });
    }
};
