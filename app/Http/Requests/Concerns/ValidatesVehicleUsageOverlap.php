<?php

namespace App\Http\Requests\Concerns;

use App\Models\VehicleUsage;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

trait ValidatesVehicleUsageOverlap
{
    protected function validateVehicleUsageOverlap(Validator $validator, ?int $ignoreUsageId = null): void
    {
        $vehicleItemId = $this->input('vehicle_item_id');
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        if (!$vehicleItemId || !$startDate) {
            return;
        }

        try {
            $normalizedStart = Carbon::createFromFormat('Y-m-d H:i:s', $startDate)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return;
        }

        $normalizedEnd = null;
        if ($endDate) {
            try {
                $normalizedEnd = Carbon::createFromFormat('Y-m-d H:i:s', $endDate)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return;
            }
        }

        if ($normalizedEnd !== null && $normalizedEnd < $normalizedStart) {
            $validator->errors()->add('end_date', 'A data de fim deve ser igual ou posterior a data de inicio.');
            return;
        }

        $newEndBoundary = $normalizedEnd ?? '9999-12-31 23:59:59';

        $overlapQuery = VehicleUsage::query()
            ->where('vehicle_item_id', $vehicleItemId)
            ->when($ignoreUsageId, fn ($query) => $query->where('id', '!=', $ignoreUsageId))
            ->where('start_date', '<=', $newEndBoundary)
            ->where(function ($query) use ($normalizedStart) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $normalizedStart);
            });

        if ($overlapQuery->exists()) {
            $validator->errors()->add('start_date', 'Ja existe uma utilizacao sobreposta para esta viatura no periodo indicado.');
        }
    }
}
