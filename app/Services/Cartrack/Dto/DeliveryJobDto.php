<?php

namespace App\Services\Cartrack\Dto;

class DeliveryJobDto extends GenericDto
{
    public function id(): int|string|null
    {
        return $this->attributes['job_id'] ?? $this->attributes['id'] ?? null;
    }

    public function status(): ?string
    {
        return $this->attributes['status'] ?? null;
    }

    public function label(): ?string
    {
        return $this->attributes['label'] ?? null;
    }

    public function scheduleTypeId(): ?int
    {
        return isset($this->attributes['schedule_type_id']) ? (int) $this->attributes['schedule_type_id'] : null;
    }
}
