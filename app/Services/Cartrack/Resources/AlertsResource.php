<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;

class AlertsResource extends AbstractResource
{
    public function list(array $query = []): CartrackResponse
    {
        return $this->client->get('/alerts', $query);
    }

    public function create(array $payload): CartrackResponse
    {
        return $this->client->post('/alerts', $payload);
    }

    public function delete(int|string $alertId): CartrackResponse
    {
        return $this->client->delete("/alerts/{$alertId}");
    }

    public function createGeofenceAlert(array $payload): CartrackResponse
    {
        return $this->client->post('/alerts/geofences', $payload);
    }
}
