<?php

namespace App\Services\Cartrack;

use App\Services\Cartrack\Exceptions\NotFoundException;

class CartrackFleetApiService extends CartrackFleetClient
{
    public function __construct(?CartrackClientConfig $config = null)
    {
        parent::__construct($config);
    }

    public function getVehicles(array $filters = []): array
    {
        return $this->vehicles()->list($filters)->toArray();
    }

    public function getVehiclesStatus(array $filters = []): array
    {
        return $this->vehicles()->status($filters)->toArray();
    }

    public function getTripsByRegistration(string $registration, array $filters = []): array
    {
        try {
            return $this->trips()->byRegistration($registration, $filters)->toArray();
        } catch (NotFoundException $e) {
            return $this->trips()->list(array_merge($filters, [
                'registration' => $registration,
            ]))->toArray();
        }
    }

    public function getFuelLevel(string $registration, array $filters = []): array
    {
        return $this->fuel()->level($registration, $filters)->toArray();
    }

    public function getEvents(array $filters = []): array
    {
        return $this->vehicles()->events($filters)->toArray();
    }

    public function getEventsByRegistration(string $registration, array $filters = []): array
    {
        return $this->vehicles()->eventsByRegistration($registration, $filters)->toArray();
    }
}
