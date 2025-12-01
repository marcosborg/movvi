<?php

namespace App\Services\Cartrack;

use App\Services\Cartrack\Dto\BaseDto;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;

class CartrackResponse
{
    public function __construct(
        protected array $decoded,
        protected Response $raw,
        public readonly ?RateLimitInfo $rateLimit = null,
    ) {
    }

    public function data(?string $key = 'data'): mixed
    {
        if ($key === null) {
            return $this->decoded;
        }

        return $this->decoded[$key] ?? null;
    }

    public function meta(?string $key = null): mixed
    {
        $meta = $this->decoded['meta'] ?? [];

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    public function links(): array
    {
        return $this->decoded['links'] ?? $this->decoded['Links'] ?? [];
    }

    public function raw(): Response
    {
        return $this->raw;
    }

    public function into(string $dtoClass): BaseDto
    {
        if (!is_subclass_of($dtoClass, BaseDto::class)) {
            throw new InvalidArgumentException("{$dtoClass} nao e um DTO valido.");
        }

        $data    = $this->data();
        $payload = is_array($data) && array_key_exists('data', $data) ? $data['data'] : $this->decoded;

        return $dtoClass::from(is_array($payload) ? $payload : []);
    }

    public function intoMany(string $dtoClass): array
    {
        if (!is_subclass_of($dtoClass, BaseDto::class)) {
            throw new InvalidArgumentException("{$dtoClass} nao e um DTO valido.");
        }

        $data    = $this->data();
        $payload = is_array($data) && array_key_exists('data', $data) ? $data['data'] : $data;

        $items = is_array($payload) ? $payload : [];

        return $dtoClass::many($items);
    }

    public function toArray(): array
    {
        return $this->decoded;
    }
}
