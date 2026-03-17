<?php

namespace App\Services\Inspections;

use App\Models\Inspection;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class InspectionPermissionsService
{
    public function ensureUserCanCreateType(User $user, string $type): void
    {
        $roles = $user->roles->pluck('title')->map(fn ($r) => mb_strtolower((string) $r))->toArray();

        $isManager = in_array('gestor', $roles, true) || in_array('admin', $roles, true);

        if (in_array($type, ['handover', 'return'], true) && !$isManager) {
            throw ValidationException::withMessages([
                'type' => 'Apenas Gestor/Admin pode criar inspecoes de Entrega ou Recolha.',
            ]);
        }
    }

    public function ensureCanEdit(Inspection $inspection): void
    {
        if ($inspection->locked_at) {
            throw ValidationException::withMessages([
                'inspection' => 'Inspecao bloqueada apos assinatura/fecho. Edicao nao permitida.',
            ]);
        }
    }
}
