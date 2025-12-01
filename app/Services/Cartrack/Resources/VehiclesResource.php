<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;

class VehiclesResource extends AbstractResource
{
    public function list(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicles', $query);
    }

    public function listAll(array $query = []): array
    {
        return $this->client->fetchAllPages('GET', '/vehicles', $query);
    }

    public function status(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicles/status', $query);
    }

    public function activity(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicle-activity', $query);
    }

    public function events(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicles/events', $query);
    }

    public function eventsByRegistration(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/vehicles/{$registration}/events", $query);
    }

    public function chargingEvents(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/vehicles/{$registration}/charging/events", $query);
    }

    public function odometer(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/vehicles/{$registration}/odometer", $query);
    }

    public function clock(string $registration, array $query = []): CartrackResponse
    {
        return $this->client->get("/vehicles/{$registration}/clock", $query);
    }

    public function nearest(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicles/nearest', $query);
    }

    public function immobiliseStatus(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicles/immobilise/status', $query);
    }

    public function battery(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicles/battery', $query);
    }

    public function evConsumption(array $payload): CartrackResponse
    {
        return $this->client->post('/vehicles/ev-consumption', $payload);
    }

    public function rangeBulk(array $payload): CartrackResponse
    {
        return $this->client->post('/vehicles/range', $payload);
    }

    public function socBulk(array $payload): CartrackResponse
    {
        return $this->client->post('/vehicles/soc', $payload);
    }

    public function vext(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicles/vext', $query);
    }

    public function shareLocationLink(string $registration, array $payload = []): CartrackResponse
    {
        return $this->client->post("/vehicles/{$registration}/share-location-link", $payload);
    }

    public function audit(array $query = []): CartrackResponse
    {
        return $this->client->get('/vehicles/audit', $query);
    }
}
