<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const REASON = 'Correcao solicitada pelo cliente: atribuir a CE-03-JP e excluir viatura de servico AX-67-OJ';

    public function up(): void
    {
        if (! Schema::hasTable('vehicle_revenue_allocation_overrides')) {
            return;
        }

        $weekId = DB::table('tvde_weeks')
            ->whereDate('start_date', '2026-08-10')
            ->whereDate('end_date', '2026-08-16')
            ->value('id');
        $driverId = DB::table('drivers')
            ->where('name', 'Richard Willian Oliveira Falavine')
            ->value('id');
        $vehicleId = DB::table('vehicle_items')
            ->where('license_plate', 'CE-03-JP')
            ->where('is_service_vehicle', false)
            ->value('id');

        if (! $weekId || ! $driverId || ! $vehicleId) {
            return;
        }

        $alreadyAssigned = DB::table('vehicle_revenue_allocation_overrides')
            ->where('tvde_week_id', $weekId)
            ->where('driver_id', $driverId)
            ->exists();

        if ($alreadyAssigned) {
            return;
        }

        DB::table('vehicle_revenue_allocation_overrides')->insert([
            'tvde_week_id' => $weekId,
            'driver_id' => $driverId,
            'vehicle_item_id' => $vehicleId,
            'created_by' => null,
            'reason' => self::REASON,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Intencionalmente vazio: um rollback de código não deve apagar uma
        // atribuição financeira histórica que possa ter sido revista depois.
    }
};
