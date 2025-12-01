<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;

class TripsResource extends AbstractResource
{
    public function list(array $query = []): CartrackResponse
    {
        return $this->client->get('/trips', $query);
    }

    public function listAll(array $query = []): array
    {
        return $this->client->fetchAllPages('GET', '/trips', $query);
    }

    public function byRegistration(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/trips/{$registration}", $query);
    }

    public function update(string $registration, array $payload): CartrackResponse
    {
        return $this->client->put("/trips/{$registration}", $payload);
    }
}
