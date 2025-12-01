<?php

namespace App\Services\Cartrack\Dto;

class AlertDto extends GenericDto
{
    public function id(): int|string|null
    {
        return $this->attributes['id'] ?? $this->attributes['alert_id'] ?? null;
    }

    public function type(): ?string
    {
        return $this->attributes['type'] ?? $this->attributes['alert_type'] ?? null;
    }
}
