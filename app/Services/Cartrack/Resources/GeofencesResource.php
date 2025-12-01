<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;

class GeofencesResource extends AbstractResource
{
    public function list(array $query = []): CartrackResponse
    {
        return $this->client->get('/geofences', $query);
    }

    public function create(array $payload): CartrackResponse
    {
        return $this->client->post('/geofences', $payload);
    }

    public function update(int|string $geofenceId, array $payload): CartrackResponse
    {
        return $this->client->put("/geofences/{$geofenceId}", $payload);
    }

    public function delete(int|string $geofenceId): CartrackResponse
    {
        return $this->client->delete("/geofences/{$geofenceId}");
    }

    public function addToGroup(int|string $geofenceId, int|string $groupId): CartrackResponse
    {
        return $this->client->put("/geofences/{$geofenceId}/groups/{$groupId}");
    }

    public function removeFromGroup(int|string $geofenceId, int|string $groupId): CartrackResponse
    {
        return $this->client->delete("/geofences/{$geofenceId}/groups/{$groupId}");
    }
}
