<?php

namespace App\Services\Cartrack;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CartrackFleetApiService
{
    protected string $baseUrl;
    protected ?string $username;
    protected ?string $password;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('cartrack.base_url'), '/');
        $this->username = config('cartrack.username');
        $this->password = config('cartrack.password');
    }

    protected function client(): PendingRequest
    {
        if (!$this->username || !$this->password) {
            throw new \RuntimeException('Configure CARTRACK_USERNAME e CARTRACK_PASSWORD no .env');
        }

        $basicToken = base64_encode("{$this->username}:{$this->password}");

        return Http::withHeaders([
                'Authorization' => "Basic {$basicToken}",
                'Accept'        => 'application/json',
            ])
            ->baseUrl($this->baseUrl . '/rest')
            ->timeout(15)
            ->retry(3, 1000, function ($exception) {
                return $exception instanceof RequestException && $exception->getCode() === 429;
            });
    }

    protected function handleResponse($response): array
    {
        if ($response->status() === 429) {
            Log::warning('Cartrack API rate limited', [
                'retry_at'    => $response->header('X-RateLimit-Retry-At'),
                'retry_after' => $response->header('X-RateLimit-Retry-After-Seconds'),
            ]);
        }

        $response->throw();

        return $response->json() ?? [];
    }

    public function getVehicles(array $filters = []): array
    {
        $response = $this->client()->get('/vehicles', $filters);

        return $this->handleResponse($response);
    }

    public function getVehiclesStatus(array $filters = []): array
    {
        $response = $this->client()->get('/vehicles/status', $filters);

        return $this->handleResponse($response);
    }

    public function getTripsByRegistration(string $registration, array $filters = []): array
    {
        $response = $this->client()->get("/trips/{$registration}", $filters);

        return $this->handleResponse($response);
    }

    public function getFuelLevel(string $registration, array $filters = []): array
    {
        $response = $this->client()->get("/fuel/level/{$registration}", $filters);

        return $this->handleResponse($response);
    }

    public function getEvents(array $filters = []): array
    {
        $response = $this->client()->get('/events', $filters);

        return $this->handleResponse($response);
    }

    public function getEventsByRegistration(string $registration, array $filters = []): array
    {
        $filters = array_merge($filters, ['registration' => $registration]);

        return $this->getEvents($filters);
    }

    public function post(string $uri, array $payload = []): array
    {
        $response = $this->client()->post($uri, $payload);

        return $this->handleResponse($response);
    }
}
