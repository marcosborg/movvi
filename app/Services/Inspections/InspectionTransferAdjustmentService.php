<?php

namespace App\Services\Inspections;

use App\Models\Adjustment;
use App\Models\Driver;
use App\Models\Inspection;
use App\Models\VehicleItem;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class InspectionTransferAdjustmentService
{
    public function buildPlan(VehicleItem $vehicle, array $transferPlan, array $input): array
    {
        $driverId = $transferPlan['mode'] === 'recolha'
            ? ($transferPlan['source_driver_id'] ?? null)
            : ($transferPlan['target_driver_id'] ?? null);

        if (!$driverId) {
            throw ValidationException::withMessages([
                'adjustment' => 'Nao foi possivel identificar o motorista do ajuste.',
            ]);
        }

        $driver = Driver::query()->find((int) $driverId);
        if (!$driver || (int) $driver->company_id !== (int) $vehicle->company_id) {
            throw ValidationException::withMessages([
                'adjustment' => 'O motorista do ajuste nao pertence a empresa da viatura.',
            ]);
        }

        $weekStart = Carbon::now()->startOfWeek();

        return [
            'name' => trim((string) $input['name']),
            'type' => (string) $input['type'],
            'amount' => round((float) $input['amount'], 2),
            'category' => Adjustment::CATEGORY_MANUAL,
            'start_date' => $weekStart->format(config('panel.date_format')),
            'end_date' => $weekStart->copy()->endOfWeek()->format(config('panel.date_format')),
            'company_id' => (int) $driver->company_id,
            'driver_id' => (int) $driver->id,
            'company_expense' => false,
            'fleet_management' => false,
            'affects_vehicle_profitability' => false,
        ];
    }

    public function createFromPlan(array $plan): Adjustment
    {
        $driverId = (int) $plan['driver_id'];
        unset($plan['driver_id']);

        $adjustment = Adjustment::create($plan);
        $adjustment->drivers()->sync([$driverId]);

        return $adjustment;
    }

    public function createForTransfer(VehicleItem $vehicle, array $transferPlan, array $input): Adjustment
    {
        return $this->createFromPlan($this->buildPlan($vehicle, $transferPlan, $input));
    }

    public function applyIfNeeded(Inspection $inspection): ?Adjustment
    {
        if ($inspection->audits()->where('action', 'transfer_adjustment_applied')->exists()) {
            return null;
        }

        $audit = $inspection->audits()
            ->where('action', 'transfer_adjustment_planned')
            ->latest('id')
            ->first();

        if (!$audit || !is_array($audit->payload)) {
            return null;
        }

        return $this->createFromPlan($audit->payload);
    }
}
