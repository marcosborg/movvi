<?php

namespace App\Services;

use App\Models\VehicleItem;
use App\Models\VehicleUsage;

class VehicleAssignmentHealthService
{
    public function current(): array
    {
        $now = now();
        $vehicles = VehicleItem::query()
            ->where(fn ($query) => $query->whereNull('suspended')->orWhere('suspended', false))
            ->orderBy('license_plate')
            ->get(['id', 'license_plate']);
        $usages = VehicleUsage::query()
            ->with('driver:id,name')
            ->where('start_date', '<=', $now)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $now))
            ->whereNotNull('driver_id')
            ->where(fn ($query) => $query->where('usage_exceptions', 'usage')->orWhereNull('usage_exceptions'))
            ->get()
            ->groupBy('vehicle_item_id');

        $unassigned = $vehicles->filter(fn ($vehicle) => $usages->get($vehicle->id, collect())->isEmpty())
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
