<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('vehicle_items', 'is_service_vehicle')) {
            return;
        }

        DB::table('vehicle_items')
            ->where('license_plate', 'AX-67-OJ')
            ->update([
                'is_service_vehicle' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Não reativar automaticamente uma viatura de serviço num rollback.
    }
};
