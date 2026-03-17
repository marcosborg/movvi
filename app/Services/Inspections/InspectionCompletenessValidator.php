<?php

namespace App\Services\Inspections;

use App\Models\Inspection;
use Illuminate\Validation\ValidationException;

class InspectionCompletenessValidator
{
    public function getMissingItems(Inspection $inspection): array
    {
        $inspection->loadMissing(['photos', 'checkItems', 'signatures']);

        $missing = [];

        if (!$inspection->driver_id) {
            $missing[] = 'Condutor nao selecionado';
        }

        $docLabels = [
            'dua' => 'Documento Unico Automovel (DUA)',
            'insurance' => 'Seguro',
            'inspection_periodic' => 'Inspecao periodica',
            'tvde_stickers' => 'Disticos TVDE',
            'no_smoking_sticker' => 'Autocolante de proibicao de fumar',
        ];

        $docChecks = [];
        foreach ($inspection->checkItems->where('group_key', 'documents') as $item) {
            $docChecks[$item->item_key] = (bool) $item->value_bool;
        }

        foreach ($docLabels as $key => $label) {
            if (empty($docChecks[$key])) {
                $missing[] = $label . ' nao assinalado';
                continue;
            }

            $hasPhoto = $inspection->photos->first(function ($photo) use ($key) {
                return $photo->category === 'document' && $photo->slot === 'doc_' . $key;
            });

            if (!$hasPhoto) {
                $missing[] = $label . ' sem foto';
            }
        }

        foreach (config('inspections.required_slots.exterior', []) as $slot) {
            $has = $inspection->photos->first(function ($photo) use ($slot) {
                return $photo->category === 'exterior' && $photo->slot === $slot;
            });
            if (!$has) {
                $label = config('inspections.slot_labels.exterior.' . $slot, $slot);
                $missing[] = 'Foto exterior em falta: ' . $label;
            }
        }

        foreach (config('inspections.required_slots.interior', []) as $slot) {
            $has = $inspection->photos->first(function ($photo) use ($slot) {
                return $photo->category === 'interior' && $photo->slot === $slot;
            });
            if (!$has) {
                $label = config('inspections.slot_labels.interior.' . $slot, $slot);
                $missing[] = 'Foto interior em falta: ' . $label;
            }
        }

        $roles = $inspection->signatures->pluck('role')->toArray();
        if (!in_array('driver', $roles, true)) {
            $missing[] = 'Assinatura do condutor em falta';
        }

        if (in_array($inspection->type, ['handover', 'return'], true) && !in_array('responsible', $roles, true)) {
            $missing[] = 'Assinatura do responsavel em falta';
        }

        return $missing;
    }

    public function assertCanClose(Inspection $inspection): void
    {
        $missing = $this->getMissingItems($inspection);

        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'inspection' => 'Inspecao incompleta: ' . implode(', ', array_unique($missing)),
            ]);
        }
    }
}
