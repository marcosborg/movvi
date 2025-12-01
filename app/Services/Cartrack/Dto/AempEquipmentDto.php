<?php

namespace App\Services\Cartrack\Dto;

class AempEquipmentDto extends GenericDto
{
    public function id(): string|int|null
    {
        return $this->attributes['id'] ?? null;
    }

    public function links(): array
    {
        return $this->attributes['Links'] ?? $this->attributes['links'] ?? [];
    }
}
