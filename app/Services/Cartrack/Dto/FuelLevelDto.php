<?php

namespace App\Services\Cartrack\Dto;

class FuelLevelDto extends GenericDto
{
    public function registration(): ?string
    {
        return $this->attributes['registration'] ?? null;
    }

    public function level(): ?float
    {
        return isset($this->attributes['fuel_level']) ? (float) $this->attributes['fuel_level'] : null;
    }

    public function estimatedUsed(): ?float
    {
        return isset($this->attributes['estimated_fuel_used']) ? (float) $this->attributes['estimated_fuel_used'] : null;
    }
}
