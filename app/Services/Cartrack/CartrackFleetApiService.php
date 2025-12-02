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
        $filters = $this->withDefaultLimit($filters, 500);

        try {
            return $this->fetchAllPages('GET', "/trips/{$registration}", $filters);
        } catch (NotFoundException $e) {
            return $this->fetchAllPages('GET', '/trips', array_merge($filters, [
                'registration' => $registration,
            ]));
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
        $filters = $this->withDefaultLimit($filters, 500);

        return $this->fetchAllPages('GET', "/vehicles/{$registration}/events", $filters);
    }

    public function getOdometerByRegistration(string $registration, array $filters = []): array
    {
        return $this->vehicles()->odometer($registration, $filters)->toArray();
    }

    protected function withDefaultLimit(array $filters, int $limit): array
    {
        if (!array_key_exists('limit', $filters) || !$filters['limit']) {
            $filters['limit'] = $limit;
        }

        return $filters;
    }
}
