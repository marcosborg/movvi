<?php

namespace App\Services;

use App\Models\VehicleItem;
use App\Models\VehicleUsage;

class VehicleAssignmentHealthService
{
    public function current(?int $companyId = null): array
    {
        $now = now();
        $vehicles = VehicleItem::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->where(fn ($query) => $query->whereNull('suspended')->orWhere('suspended', false))
            ->where(fn ($query) => $query->whereNull('is_service_vehicle')->orWhere('is_service_vehicle', false))
            ->orderBy('license_plate')
            ->get(['id', 'license_plate', 'driver_id']);
        $activeUsages = VehicleUsage::query()
            ->with('driver:id,name')
            ->when($companyId, fn ($query) => $query->whereHas(
                'vehicle_item',
                fn ($vehicleQuery) => $vehicleQuery->where('company_id', $companyId)
            ))
            ->where('start_date', '<=', $now)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $now->copy()->startOfDay()))
            ->get()
            ->filter(function (VehicleUsage $usage) use ($now) {
                if (!$usage->getRawOriginal('end_date')) {
                    return true;
                }

                $end = \Carbon\Carbon::parse($usage->getRawOriginal('end_date'));

                return ($end->isStartOfDay() ? $end->endOfDay() : $end)->greaterThanOrEqualTo($now);
            });
        $usages = $activeUsages
            ->whereNotNull('driver_id')
            ->filter(fn (VehicleUsage $usage) => !$usage->usage_exceptions || $usage->usage_exceptions === 'usage')
            ->groupBy('vehicle_item_id');
        $allUsagesByVehicle = $activeUsages->groupBy('vehicle_item_id');

        $unassigned = $vehicles->filter(fn ($vehicle) => !$vehicle->driver_id
            && $allUsagesByVehicle->get($vehicle->id, collect())->isEmpty())
            ->map(fn ($vehicle) => ['vehicle_id' => (int) $vehicle->id, 'license_plate' => (string) $vehicle->license_plate])
            ->values();
        $multipleVehicles = $vehicles->map(function ($vehicle) use ($usages) {
            $active = $usages->get($vehicle->id, collect());
            if ($active->pluck('driver_id')->unique()->count() < 2) {
                return null;
            }

            return [
                'vehicle_id' => (int) $vehicle->id,
                'license_plate' => (string) $vehicle->license_plate,
                'drivers' => $active->pluck('driver.name')->filter()->unique()->values()->all(),
            ];
        })->filter()->values();

        $driverVehicles = $usages->flatten()->groupBy('driver_id')->map(function ($active) use ($vehicles) {
            $vehicleIds = $active->pluck('vehicle_item_id')->unique();
            if ($vehicleIds->count() < 2) {
                return null;
            }
            $driver = $active->first()->driver;

            return [
                'driver_id' => (int) $driver->id,
                'driver_name' => (string) $driver->name,
                'vehicles' => $vehicles->whereIn('id', $vehicleIds)->pluck('license_plate')->values()->all(),
            ];
        })->filter()->values();

        return [
            'unassigned_vehicles' => $unassigned,
            'vehicles_with_multiple_drivers' => $multipleVehicles,
            'drivers_with_multiple_vehicles' => $driverVehicles,
            'counts' => [
                'unassigned_vehicles' => $unassigned->count(),
                'vehicles_with_multiple_drivers' => $multipleVehicles->count(),
                'drivers_with_multiple_vehicles' => $driverVehicles->count(),
            ],
        ];
    }
}
