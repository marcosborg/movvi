<?php

namespace App\Services\Cartrack\Dto;

class GeofenceDto extends GenericDto
{
    public function id(): int|string|null
    {
        return $this->attributes['geofence_id'] ?? $this->attributes['id'] ?? null;
    }

    public function name(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function isGlobal(): ?bool
    {
        return isset($this->attributes['is_global']) ? (bool) $this->attributes['is_global'] : null;
    }
}
