<?php

namespace App\Services\Cartrack\Dto;

/**
 * @see definitions.vehicles_status_response in the OpenAPI spec.
 */
class VehicleStatusDto extends GenericDto
{
    public function registration(): ?string
    {
        return $this->attributes['registration'] ?? $this->attributes['license_plate'] ?? null;
    }

    public function latitude(): ?float
    {
        return isset($this->attributes['latitude']) ? (float) $this->attributes['latitude'] : null;
    }

    public function longitude(): ?float
    {
        return isset($this->attributes['longitude']) ? (float) $this->attributes['longitude'] : null;
    }

    public function ignitionOn(): ?bool
    {
        return isset($this->attributes['ignition_on']) ? (bool) $this->attributes['ignition_on'] : null;
    }

    public function speedKph(): ?float
    {
        return isset($this->attributes['speed']) ? (float) $this->attributes['speed'] : null;
    }

    public function driver(): ?array
    {
        return $this->attributes['driver'] ?? $this->attributes['default_driver'] ?? null;
    }
}
