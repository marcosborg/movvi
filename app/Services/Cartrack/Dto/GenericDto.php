<?php

namespace App\Services\Cartrack\Dto;

class GenericDto extends BaseDto
{
    public function __construct(protected array $attributes)
    {
    }

    public static function from(array $payload): static
    {
        return new static($payload);
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
