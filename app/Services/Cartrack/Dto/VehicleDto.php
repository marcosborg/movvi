<?php

namespace App\Services\Cartrack\Dto;

/**
 * @see \resources\openapi\cartrack_fleet.json definitions.vehicle_get
 */
class VehicleDto extends GenericDto
{
    public function id(): int|string|null
    {
        return $this->attributes['id'] ?? $this->attributes['vehicle_id'] ?? null;
    }

    public function registration(): ?string
    {
        return $this->attributes['registration'] ?? $this->attributes['license_plate'] ?? null;
    }

    public function vin(): ?string
    {
        return $this->attributes['vin'] ?? null;
    }

    public function brand(): ?string
    {
        return $this->attributes['brand'] ?? $this->attributes['make'] ?? null;
    }

    public function model(): ?string
    {
        return $this->attributes['model'] ?? null;
    }
}
