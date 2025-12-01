<?php

namespace App\Services\Cartrack\Dto;

/**
 * @see definitions.trips_get_response in the OpenAPI spec.
 */
class TripDto extends GenericDto
{
    public function registration(): ?string
    {
        return $this->attributes['registration'] ?? null;
    }

    public function distance(): ?float
    {
        if (isset($this->attributes['distance'])) {
            return (float) $this->attributes['distance'];
        }

        if (isset($this->attributes['total_distance'])) {
            return (float) $this->attributes['total_distance'];
        }

        return null;
    }

    public function startedAt(): ?string
    {
        return $this->attributes['start_timestamp'] ?? $this->attributes['startTimestamp'] ?? null;
    }

    public function endedAt(): ?string
    {
        return $this->attributes['end_timestamp'] ?? $this->attributes['endTimestamp'] ?? null;
    }
}
