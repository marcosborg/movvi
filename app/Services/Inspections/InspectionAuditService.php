<?php

namespace App\Services\Inspections;

use App\Models\Inspection;
use App\Models\InspectionAudit;

class InspectionAuditService
{
    public function log(Inspection $inspection, string $action, ?array $payload = null): void
    {
        InspectionAudit::create([
            'inspection_id' => $inspection->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
