<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;

class AempResource extends AbstractResource
{
    public function equipmentSnapshot(string $id, array $query = []): CartrackResponse
    {
        return $this->client->get(
            "/aemp/iso15143-3/beta/equipment/{$id}",
            $query,
            [],
            $this->client->config()->aempAccept
        );
    }

    public function fleetSnapshot(array $query = []): CartrackResponse
    {
        return $this->client->get(
            '/aemp/iso15143-3/beta/fleet',
            $query,
            [],
            $this->client->config()->aempAccept
        );
    }

    public function fleetSnapshotAll(array $query = []): array
    {
        return $this->client->fetchAllPages(
            'GET',
            '/aemp/iso15143-3/beta/fleet',
            $query,
            [],
            $this->client->config()->aempAccept
        );
    }

    public function route(array $query = []): CartrackResponse
    {
        return $this->client->get(
            '/aemp/iso15143-3/beta/route',
            $query,
            [],
            $this->client->config()->aempAccept
        );
    }
}
