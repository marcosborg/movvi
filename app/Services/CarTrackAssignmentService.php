<?php

namespace App\Services;

use App\Models\CarTrack;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use Illuminate\Support\Facades\Log;

class CarTrackAssignmentService
{
    public function assign(CarTrack $carTrack): CarTrack
    {
        $this->assignWithDiagnostics($carTrack);

        return $carTrack;
    }

    public function assignWithDiagnostics(CarTrack $carTrack, bool $persist = true, bool $logDiagnostics = true): array
    {
        $vehicleItemId = $this->resolveVehicleItemIdFromPlate($carTrack->license_plate);
        $usageMatches = [];

        if ($vehicleItemId && $carTrack->date) {
            $usageMatches = $this->resolveDriverUsageMatches($vehicleItemId, $carTrack->date);
        }

        $decision = $this->buildAssignmentDecision(
            $vehicleItemId,
            $carTrack->date,
            $usageMatches
        );

        $decision['vehicle_item_id'] = $vehicleItemId;
        $decision['current_driver_id'] = $carTrack->driver_id;
        $decision['current_vehicle_item_id'] = $carTrack->vehicle_item_id;
        $decision['current_vehicle_usage_id'] = $carTrack->vehicle_usage_id;
        $decision['current_assignment_status'] = $carTrack->assignment_status;
        $decision['changed'] = (int) $carTrack->vehicle_item_id !== (int) ($decision['vehicle_item_id'] ?? null)
            || (int) $carTrack->driver_id !== (int) ($decision['driver_id'] ?? null)
            || (int) $carTrack->vehicle_usage_id !== (int) ($decision['vehicle_usage_id'] ?? null)
            || (string) $carTrack->assignment_status !== (string) ($decision['status'] ?? null);

        if ($persist) {
            $carTrack->forceFill([
                'vehicle_item_id' => $decision['vehicle_item_id'],
                'driver_id' => $decision['driver_id'],
                'vehicle_usage_id' => $decision['vehicle_usage_id'],
                'assignment_status' => $decision['status'],
                'assignment_notes' => $this->assignmentNotes($decision),
            ])->save();
        }

        if ($logDiagnostics) {
            $this->logAssignmentDiagnostics($carTrack, $decision);
        }

        return $decision;
    }

    protected function resolveVehicleItemIdFromPlate(?string $plate): ?int
    {
        $normalizedPlate = $this->normalizePlate($plate);

        if ($normalizedPlate === '') {
            return null;
        }

        return VehicleItem::withTrashed()
            ->whereRaw("REPLACE(REPLACE(UPPER(license_plate), '-', ''), ' ', '') = ?", [$normalizedPlate])
            ->value('id');
    }

    protected function resolveDriverUsageMatches(int $vehicleItemId, $passageAt): array
    {
        return VehicleUsage::query()
            ->where('vehicle_item_id', $vehicleItemId)
            ->where('start_date', '<=', $passageAt)
            ->where(function ($query) use ($passageAt) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $passageAt);
            })
            ->where(function ($query) {
                $query->whereNull('usage_exceptions')
                    ->orWhere('usage_exceptions', 'usage');
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'driver_id'])
            ->map(fn (VehicleUsage $usage) => [
                'id' => (int) $usage->id,
                'driver_id' => $usage->driver_id !== null ? (int) $usage->driver_id : null,
            ])
            ->all();
    }

    protected function buildAssignmentDecision(?int $vehicleItemId, $passageAt, array $usageMatches): array
    {
        if (!$passageAt) {
            return [
                'status' => CarTrack::STATUS_MISSING_TIMESTAMP,
                'driver_id' => null,
                'vehicle_usage_id' => null,
                'usage_ids' => [],
            ];
        }

        if (!$vehicleItemId) {
            return [
                'status' => CarTrack::STATUS_VEHICLE_NOT_FOUND,
                'driver_id' => null,
                'vehicle_usage_id' => null,
                'usage_ids' => [],
            ];
        }

        if (count($usageMatches) === 0) {
            return [
                'status' => CarTrack::STATUS_NO_USAGE_MATCH,
                'driver_id' => null,
                'vehicle_usage_id' => null,
                'usage_ids' => [],
            ];
        }

        if (count($usageMatches) > 1) {
            return [
                'status' => CarTrack::STATUS_MULTIPLE_USAGE_MATCHES,
                'driver_id' => null,
                'vehicle_usage_id' => null,
                'usage_ids' => array_column($usageMatches, 'id'),
            ];
        }

        $match = $usageMatches[0];

        if (($match['driver_id'] ?? null) === null) {
            return [
                'status' => CarTrack::STATUS_NO_USAGE_MATCH,
                'driver_id' => null,
                'vehicle_usage_id' => null,
                'usage_ids' => [$match['id']],
            ];
        }

        return [
            'status' => CarTrack::STATUS_ASSIGNED,
            'driver_id' => $match['driver_id'],
            'vehicle_usage_id' => $match['id'],
            'usage_ids' => [$match['id']],
        ];
    }

    protected function assignmentNotes(array $decision): ?string
    {
        $usageIds = $decision['usage_ids'] ?? [];

        if (empty($usageIds)) {
            return null;
        }

        return 'vehicle_usage_ids=' . implode(',', $usageIds);
    }

    protected function normalizePlate(?string $plate): string
    {
        return strtoupper(str_replace(['-', ' '], '', trim((string) $plate)));
    }

    protected function logAssignmentDiagnostics(CarTrack $carTrack, array $decision): void
    {
        if ($decision['status'] === CarTrack::STATUS_ASSIGNED) {
            return;
        }

        Log::warning('Via Verde assignment unresolved', [
            'car_track_id' => $carTrack->id,
            'license_plate' => $carTrack->license_plate,
            'vehicle_item_id' => $decision['vehicle_item_id'] ?? null,
            'date' => $carTrack->date,
            'tvde_week_id' => $carTrack->tvde_week_id,
            'status' => $decision['status'],
            'usage_ids' => $decision['usage_ids'] ?? [],
        ]);
    }
}
