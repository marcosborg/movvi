<?php

namespace App\Services;

use App\Models\CombustionTransaction;
use App\Models\TvdeActivityEntry;
use Illuminate\Support\Collection;

class WeeklyImportReconciliationService
{
    public function forWeek(int $weekId, ?int $companyId = null): array
    {
        $activityEntries = TvdeActivityEntry::query()
            ->with(['driver:id,name', 'vehicle_item:id,license_plate', 'tvde_operator:id,name'])
            ->where('tvde_week_id', $weekId)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->get();

        $platforms = $activityEntries->groupBy(fn ($entry) => strtolower((string) optional($entry->tvde_operator)->name))
            ->map(fn (Collection $entries, string $platform) => $this->activitySummary($platform, $entries))
            ->values()
            ->all();

        $fuel = CombustionTransaction::query()
            ->with(['driver:id,name', 'vehicle_item:id,license_plate'])
            ->where('tvde_week_id', $weekId)
            ->get();

        return [
            'platforms' => $platforms,
            'fuel' => $this->fuelSummary($fuel),
        ];
    }

    private function activitySummary(string $platform, Collection $entries): array
    {
        // The company/driver report is reconciled against the driver. Vehicle
        // allocation is a separate profitability concern and older platform
        // exports legitimately have no trip timestamp with which to determine
        // a vehicle. Treating those recognised drivers as wholly unassigned
        // made valid imports look broken in the weekly report.
        $assigned = $entries->whereNotNull('driver_id');
        $pending = $entries->whereNull('driver_id');

        return [
            'label' => ucfirst($platform ?: 'Plataforma'),
            'read' => $entries->count(),
            'assigned' => $assigned->count(),
            'pending' => $pending->count(),
            'gross' => round((float) $entries->sum('gross'), 2),
            'net' => round((float) $entries->sum('net'), 2),
            'assigned_value' => round((float) $assigned->sum('net'), 2),
            'pending_value' => round((float) $pending->sum('net'), 2),
            'pending_items' => $pending->map(fn ($entry) => [
                'reference' => $entry->driver_code,
                'driver' => optional($entry->driver)->name,
                'reason' => 'Motorista não reconhecido no ficheiro.',
            ])->values()->all(),
        ];
    }

    private function fuelSummary(Collection $entries): array
    {
        $assigned = $entries->filter(fn ($entry) => $entry->driver_id || $entry->vehicle_item_id);
        $pending = $entries->filter(fn ($entry) => ! $entry->driver_id && ! $entry->vehicle_item_id);

        return [
            'label' => 'PRIO/abastecimentos',
            'read' => $entries->count(),
            'assigned' => $assigned->count(),
            'pending' => $pending->count(),
            'total' => round((float) $entries->sum('total'), 2),
            'assigned_value' => round((float) $assigned->sum('total'), 2),
            'pending_value' => round((float) $pending->sum('total'), 2),
            'pending_items' => $pending->map(fn ($entry) => [
                'reference' => $entry->card,
                'driver' => null,
                'reason' => 'Cartão ou matrícula sem atribuição temporal válida na data do abastecimento.',
            ])->values()->all(),
        ];
    }
}
