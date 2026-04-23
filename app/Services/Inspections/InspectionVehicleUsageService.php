<?php

namespace App\Services\Inspections;

use App\Models\Inspection;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class InspectionVehicleUsageService
{
    public function buildPlan(Inspection $inspection, array $data): ?array
    {
        $transferMode = isset($data['transfer_mode']) ? (string) $data['transfer_mode'] : '';

        if ($transferMode === '') {
            return $this->inferPlan($inspection, $data);
        }

        return $this->normalizePlan($inspection, [
            'mode' => $transferMode,
            'source_driver_id' => $data['source_driver_id'] ?? $inspection->vehicle?->driver_id,
            'target_driver_id' => $data['driver_id'] ?? $inspection->driver_id,
        ]);
    }

    public function applyIfNeeded(Inspection $inspection): void
    {
        if ($inspection->audits()->where('action', 'vehicle_usage_applied')->exists()) {
            return;
        }

        $plan = $this->loadPlan($inspection);
        if (!$plan) {
            return;
        }

        $vehicle = $inspection->vehicle()->firstOrFail();
        $this->applyPlanToVehicle($vehicle, $plan);
    }

    public function applyDirectTransfer(VehicleItem $vehicle, array $plan): array
    {
        $normalizedPlan = $this->normalizeDirectPlan($vehicle, $plan);

        if (!$normalizedPlan) {
            throw ValidationException::withMessages([
                'transfer_mode' => 'Plano de utilizacao invalido.',
            ]);
        }

        $this->applyPlanToVehicle($vehicle, $normalizedPlan);

        return $normalizedPlan;
    }

    public function loadPlan(Inspection $inspection): ?array
    {
        $audit = $inspection->audits()
            ->where('action', 'vehicle_usage_planned')
            ->latest('id')
            ->first();

        if (!$audit || !is_array($audit->payload)) {
            return $this->inferPlan($inspection, []);
        }

        return $this->normalizePlan($inspection, $audit->payload);
    }

    private function inferPlan(Inspection $inspection, array $data): ?array
    {
        return match ((string) $inspection->type) {
            'return' => $this->normalizePlan($inspection, [
                'mode' => 'recolha',
                'source_driver_id' => $data['source_driver_id'] ?? $inspection->driver_id ?? $inspection->vehicle?->driver_id,
                'target_driver_id' => null,
            ]),
            'handover' => $this->normalizePlan($inspection, [
                'mode' => ($inspection->vehicle?->driver_id || !empty($data['source_driver_id'])) ? 'passagem' : 'entrega',
                'source_driver_id' => $data['source_driver_id'] ?? $inspection->vehicle?->driver_id,
                'target_driver_id' => $data['driver_id'] ?? $inspection->driver_id,
            ]),
            default => null,
        };
    }

    private function normalizePlan(Inspection $inspection, array $plan): ?array
    {
        $mode = (string) ($plan['mode'] ?? '');
        if (!in_array($mode, ['entrega', 'recolha', 'passagem'], true)) {
            return null;
        }

        $sourceDriverId = isset($plan['source_driver_id']) && $plan['source_driver_id'] !== null
            ? (int) $plan['source_driver_id']
            : ($inspection->vehicle?->driver_id ? (int) $inspection->vehicle->driver_id : null);

        $targetDriverId = $inspection->driver_id
            ? (int) $inspection->driver_id
            : (isset($plan['target_driver_id']) && $plan['target_driver_id'] !== null
                ? (int) $plan['target_driver_id']
                : null);

        if ($mode === 'recolha') {
            $targetDriverId = null;
        }

        return [
            'mode' => $mode,
            'source_driver_id' => $sourceDriverId,
            'target_driver_id' => $targetDriverId,
        ];
    }

    private function normalizeDirectPlan(VehicleItem $vehicle, array $plan): ?array
    {
        $mode = (string) ($plan['mode'] ?? '');
        if (!in_array($mode, ['entrega', 'recolha', 'passagem'], true)) {
            return null;
        }

        $sourceDriverId = isset($plan['source_driver_id']) && $plan['source_driver_id'] !== null
            ? (int) $plan['source_driver_id']
            : ($vehicle->driver_id ? (int) $vehicle->driver_id : null);

        $targetDriverId = isset($plan['target_driver_id']) && $plan['target_driver_id'] !== null
            ? (int) $plan['target_driver_id']
            : null;

        if ($mode === 'recolha') {
            $targetDriverId = null;
        }

        return [
            'mode' => $mode,
            'source_driver_id' => $sourceDriverId,
            'target_driver_id' => $targetDriverId,
        ];
    }

    private function applyPlanToVehicle(VehicleItem $vehicle, array $plan): void
    {
        $appliedAt = Carbon::now();
        $closedAt = $appliedAt->copy()->subSecond();
        $activeUsages = VehicleUsage::query()
            ->where('vehicle_item_id', $vehicle->id)
            ->where(function ($query) use ($appliedAt) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $appliedAt->format('Y-m-d H:i:s'));
            })
            ->get();

        $targetDriverId = isset($plan['target_driver_id']) ? (int) $plan['target_driver_id'] : null;

        foreach ($activeUsages as $usage) {
            $usage->update([
                'end_date' => $closedAt->format('Y-m-d H:i:s'),
            ]);
        }

        if (in_array($plan['mode'], ['entrega', 'passagem'], true)) {
            if (!$targetDriverId) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Selecione o motorista destino antes de concluir a operacao.',
                ]);
            }

            VehicleUsage::create([
                'driver_id' => $targetDriverId,
                'vehicle_item_id' => $vehicle->id,
                'start_date' => $appliedAt->format('Y-m-d H:i:s'),
                'end_date' => $appliedAt->copy()->addYear()->format('Y-m-d H:i:s'),
                'usage_exceptions' => 'usage',
            ]);
        }

        $vehicle->update([
            'driver_id' => in_array($plan['mode'], ['entrega', 'passagem'], true) ? $targetDriverId : null,
        ]);
    }
}
