<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;

class FuelResource extends AbstractResource
{
    public function level(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/fuel/level/{$registration}", $query);
    }

    public function levelHistory(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/fuel/level/history/{$registration}", $query);
    }

    public function consumedBulk(array $payload): CartrackResponse
    {
        return $this->client->post('/fuel/consumed', $payload);
    }

    public function consumedByRegistration(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/fuel/consumed/{$registration}", $query);
    }

    public function levelBulk(array $payload): CartrackResponse
    {
        return $this->client->post('/fuel/level', $payload);
    }

    public function fills(array $query = []): CartrackResponse
    {
        return $this->client->get('/fuel/fills', $query);
    }

    public function fillsByRegistration(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/fuel/fills/{$registration}", $query);
    }
}
