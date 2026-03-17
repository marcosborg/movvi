<?php

namespace App\Services\Inspections;

use App\Models\Inspection;
use App\Models\InspectionDamage;
use Illuminate\Validation\ValidationException;

class InspectionSequenceService
{
    public function resolvePreviousInspection(int $vehicleId): ?Inspection
    {
        return Inspection::query()
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['signed', 'closed'])
            ->latest('completed_at')
            ->latest('id')
            ->first();
    }

    public function validateCreationSequence(string $type, int $vehicleId): ?Inspection
    {
        $previous = $this->resolvePreviousInspection($vehicleId);

        if (!$previous) {
            if ($type !== 'handover') {
                throw ValidationException::withMessages([
                    'type' => 'Sem historico para esta viatura. O primeiro tipo permitido e Entrega.',
                ]);
            }

            return null;
        }

        $allowedByPreviousType = [
            'handover' => ['return'],
            'return' => ['handover'],
        ];

        $previousType = (string) $previous->type;
        $allowed = $allowedByPreviousType[$previousType] ?? [];

        if (!in_array($type, $allowed, true)) {
            $previousLabel = $this->label($previousType);
            $allowedLabels = implode(', ', array_map(fn (string $value) => $this->label($value), $allowed));

            throw ValidationException::withMessages([
                'type' => "Circuito invalido. Apos {$previousLabel} apenas permite: {$allowedLabels}.",
            ]);
        }

        return $previous;
    }

    public function cloneOpenDamages(Inspection $inspection): void
    {
        if (!$inspection->previous_inspection_id) {
            return;
        }

        $previousDamages = InspectionDamage::query()
            ->where('inspection_id', $inspection->previous_inspection_id)
            ->where('is_resolved', false)
            ->get();

        foreach ($previousDamages as $damage) {
            InspectionDamage::create([
                'inspection_id' => $inspection->id,
                'origin_damage_id' => $damage->id,
                'scope' => $damage->scope,
                'location' => $damage->location,
                'part' => $damage->part,
                'part_section' => $damage->part_section,
                'damage_type' => $damage->damage_type,
                'notes' => $damage->notes,
                'is_resolved' => false,
            ]);
        }
    }

    private function label(string $type): string
    {
        return (string) config('inspections.type_labels.' . $type, $type);
    }
}
