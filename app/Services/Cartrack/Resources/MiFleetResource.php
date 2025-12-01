<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;
use InvalidArgumentException;

class MiFleetResource extends AbstractResource
{
    protected array $allowedResources = [
        'accident',
        'maintenance',
        'toll',
        'fuel',
        'consumable',
        'financing',
        'insurance',
        'oil',
        'purchase',
        'leasing-cost',
        'breakdown',
        'cleaning',
        'tax',
        'driver-cost',
        'driver-license',
        'rental-cost',
        'vehicle-license',
        'fines',
    ];

    public function list(string $resource, array $query = []): CartrackResponse
    {
        $resource = $this->guardResource($resource);

        return $this->client->get("/mifleet/{$resource}", $query);
    }

    public function get(string $resource, int|string $id): CartrackResponse
    {
        $resource = $this->guardResource($resource);

        return $this->client->get("/mifleet/{$resource}/{$id}");
    }

    public function create(string $resource, array $payload): CartrackResponse
    {
        $resource = $this->guardResource($resource);

        return $this->client->post("/mifleet/{$resource}", $payload);
    }

    public function update(string $resource, int|string $id, array $payload): CartrackResponse
    {
        $resource = $this->guardResource($resource);

        return $this->client->put("/mifleet/{$resource}/{$id}", $payload);
    }

    public function delete(string $resource, int|string $id): CartrackResponse
    {
        $resource = $this->guardResource($resource);

        return $this->client->delete("/mifleet/{$resource}/{$id}");
    }

    protected function guardResource(string $resource): string
    {
        $normalized = strtolower($resource);

        if (!in_array($normalized, $this->allowedResources, true)) {
            throw new InvalidArgumentException("Recurso MiFleet inválido: {$resource}");
        }

        return $normalized;
    }
}
