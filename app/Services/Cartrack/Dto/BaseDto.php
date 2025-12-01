<?php

namespace App\Services\Cartrack\Dto;

abstract class BaseDto implements \JsonSerializable
{
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    abstract public function toArray(): array;

    public static function many(array $items): array
    {
        return array_map(fn ($item) => static::from($item), $items);
    }

    abstract public static function from(array $payload): static;
}
