<?php

namespace App\Services\Cartrack;

use App\Services\Cartrack\Exceptions\AuthenticationException;
use App\Services\Cartrack\Exceptions\AuthorizationException;
use App\Services\Cartrack\Exceptions\ClientException;
use App\Services\Cartrack\Exceptions\NotFoundException;
use App\Services\Cartrack\Exceptions\RateLimitException;
use App\Services\Cartrack\Exceptions\ServerException;
use App\Services\Cartrack\Exceptions\TransportException;
use App\Services\Cartrack\Exceptions\ValidationException;
use App\Services\Cartrack\OpenApi\CartrackOpenApi;
use App\Services\Cartrack\Resources\AempResource;
use App\Services\Cartrack\Resources\AlertsResource;
use App\Services\Cartrack\Resources\DeliveryResource;
use App\Services\Cartrack\Resources\FuelResource;
use App\Services\Cartrack\Resources\GeofencesResource;
use App\Services\Cartrack\Resources\HealthResource;
use App\Services\Cartrack\Resources\MiFleetResource;
use App\Services\Cartrack\Resources\TripsResource;
use App\Services\Cartrack\Resources\VehiclesResource;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CartrackFleetClient
{
    protected CartrackClientConfig $config;
    protected string $authHeader;
    protected ?CartrackOpenApi $openApi;

    /** @var array<string, object> */
    protected array $resources = [];

    public function __construct(?CartrackClientConfig $config = null, ?CartrackOpenApi $openApi = null)
    {
        $this->config = $config ?: CartrackClientConfig::fromConfig(config('cartrack', []));
        $this->openApi = $openApi ?: $this->loadOpenApi();

        if ($this->openApi?->basePath()) {
            $this->config->basePath = $this->openApi->basePath();
        }

        $this->authHeader = 'Basic ' . base64_encode("{$this->config->username}:{$this->config->password}");
    }

    public function config(): CartrackClientConfig
    {
        return $this->config;
    }

    public function vehicles(): VehiclesResource
    {
        return $this->resource('vehicles', VehiclesResource::class);
    }

    public function trips(): TripsResource
    {
        return $this->resource('trips', TripsResource::class);
    }

    public function fuel(): FuelResource
    {
        return $this->resource('fuel', FuelResource::class);
    }

    public function geofences(): GeofencesResource
    {
        return $this->resource('geofences', GeofencesResource::class);
    }

    public function alerts(): AlertsResource
    {
        return $this->resource('alerts', AlertsResource::class);
    }

    public function delivery(): DeliveryResource
    {
        return $this->resource('delivery', DeliveryResource::class);
    }

    public function mifleet(): MiFleetResource
    {
        return $this->resource('mifleet', MiFleetResource::class);
    }

    public function aemp(): AempResource
    {
        return $this->resource('aemp', AempResource::class);
    }

    public function health(): HealthResource
    {
        return $this->resource('health', HealthResource::class);
    }

    public function get(string $path, array $query = [], array $headers = [], ?string $accept = null): CartrackResponse
    {
        return $this->request('GET', $path, [
            'query'   => $query,
            'headers' => $headers,
            'accept'  => $accept,
        ]);
    }

    public function post(string $path, array $payload = [], array $query = [], array $headers = [], ?string $accept = null): CartrackResponse
    {
        return $this->request('POST', $path, [
            'query'   => $query,
            'headers' => $headers,
            'json'    => $payload,
            'accept'  => $accept,
        ]);
    }

    public function put(string $path, array $payload = [], array $query = [], array $headers = [], ?string $accept = null): CartrackResponse
    {
        return $this->request('PUT', $path, [
            'query'   => $query,
            'headers' => $headers,
            'json'    => $payload,
            'accept'  => $accept,
        ]);
    }

    public function delete(string $path, array $query = [], array $headers = [], ?string $accept = null): CartrackResponse
    {
        return $this->request('DELETE', $path, [
            'query'   => $query,
            'headers' => $headers,
            'accept'  => $accept,
        ]);
    }

    public function upload(string $path, string $filePath, array $query = [], array $headers = [], ?string $accept = null): CartrackResponse
    {
        if (!is_file($filePath)) {
            throw new RuntimeException("Ficheiro não encontrado para upload: {$filePath}");
        }

        $path = $this->normalizePath($path);
        $request = $this->pendingRequest($headers, $accept)
            ->attach('file', file_get_contents($filePath), basename($filePath));

        $response = $request->post($this->config->baseUrl . $this->config->basePath . '/' . $path, $query);

        return $this->handleResponse($response);
    }

    public function fetchAllPages(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        ?string $accept = null
    ): array {
        $currentPage = $query['page'] ?? 1;
        $limit       = $query['limit'] ?? null;
        $all         = [];

        while (true) {
            $pageQuery = $query;
            $pageQuery['page'] = $currentPage;
            if ($limit !== null) {
                $pageQuery['limit'] = $limit;
            }

            $response = $this->request($method, $path, [
                'query'   => $pageQuery,
                'headers' => $headers,
                'accept'  => $accept,
            ]);

            $payload   = $response->toArray();
            $chunk     = $this->extractPageItems($payload);
            $all       = array_merge($all, $chunk);
            $nextPage  = $this->resolveNextPage($payload, $currentPage);

            if ($nextPage === null) {
                break;
            }

            $currentPage = $nextPage;
        }

        return $all;
    }

    public function request(string $method, string $path, array $options = []): CartrackResponse
    {
        $path      = $this->normalizePath($path);
        $query     = $options['query'] ?? [];
        $headers   = $options['headers'] ?? [];
        $accept    = $options['accept'] ?? null;
        $json      = $options['json'] ?? null;
        $body      = $options['body'] ?? null;
        $attempt   = 0;
        $backoffMs = $this->config->initialBackoffMs;

        do {
            $attempt++;

            $request = $this->pendingRequest($headers, $accept);

            $payload = array_filter([
                'query' => $query ?: null,
                'json'  => $json,
                'body'  => $body,
            ], fn ($v) => $v !== null);

            try {
                $response = $request->send($method, $path, $payload);
            } catch (\Throwable $e) {
                throw new TransportException($e->getMessage(), (int) $e->getCode(), null, [], $e);
            }

            if ($response->status() === 429) {
                $rateInfo = $this->extractRateLimitInfo($response);

                if ($attempt >= $this->config->maxAttempts) {
                    throw new RateLimitException(
                        'Limite de chamadas excedido',
                        429,
                        $response->json(),
                        $response->headers(),
                        $rateInfo
                    );
                }

                $sleepMs = $this->computeBackoffMs($rateInfo, $backoffMs);
                usleep($sleepMs * 1000);
                $backoffMs = min($this->config->maxBackoffMs, $backoffMs * 2);
                continue;
            }

            return $this->handleResponse($response);
        } while (true);
    }

    protected function pendingRequest(array $headers = [], ?string $accept = null): PendingRequest
    {
        $baseHeaders = array_merge([
            'Authorization' => $this->authHeader,
            'Accept'        => $accept ?: $this->config->defaultAccept,
        ], $this->config->defaultHeaders, $headers);

        return Http::withHeaders($baseHeaders)
            ->timeout($this->config->timeoutSeconds)
            ->baseUrl($this->config->baseUrl . $this->config->basePath)
            ->withOptions([
                'http_errors' => false,
            ]);
    }

    protected function handleResponse(Response $response): CartrackResponse
    {
        $rateInfo = $this->extractRateLimitInfo($response);
        $decoded  = $response->json();
        if ($decoded === null && $response->body() !== null) {
            $decoded = ['raw' => $response->body()];
        }

        if ($response->successful()) {
            return new CartrackResponse(is_array($decoded) ? $decoded : [], $response, $rateInfo);
        }

        $this->throwForResponse($response, $rateInfo);
    }

    protected function throwForResponse(Response $response, ?RateLimitInfo $rateInfo = null): never
    {
        $status  = $response->status();
        $headers = $response->headers();
        $body    = $response->json();
        if ($body === null && $response->body() !== null) {
            $body = ['raw' => $response->body()];
        }
        $message = $body['error']['message'] ?? $body['message'] ?? $response->body();

        if ($status === 401) {
            throw new AuthenticationException($message ?: 'Não autenticado.', 401, $body, $headers);
        }

        if ($status === 403) {
            throw new AuthorizationException($message ?: 'Não autorizado.', 403, $body, $headers);
        }

        if ($status === 404) {
            throw new NotFoundException($message ?: 'Recurso não encontrado.', 404, $body, $headers);
        }

        if ($status === 422) {
            $errors = $body['error']['data'] ?? $body['errors'] ?? [];

            throw new ValidationException($message ?: 'Erro de validação.', 422, $body, $headers, $errors);
        }

        if ($status === 429) {
            throw new RateLimitException($message ?: 'Rate limit atingido.', 429, $body, $headers, $rateInfo);
        }

        if ($status >= 500) {
            throw new ServerException($message ?: 'Erro interno do servidor.', $status, $body, $headers);
        }

        throw new ClientException($message ?: 'Erro na chamada da API.', $status, $body, $headers);
    }

    protected function computeBackoffMs(?RateLimitInfo $info, int $baseBackoffMs): int
    {
        $base = $info?->retryAfterMs() ?? $baseBackoffMs;

        if ($info?->retryAtTimestamp) {
            $nowMs  = (int) round(microtime(true) * 1000);
            $delta  = max(0, ($info->retryAtTimestamp * 1000) - $nowMs);
            $base   = max($base, $delta);
        }

        $jitter = (int) ($base * $this->config->jitterFactor);
        $min = max(0, $base - $jitter);
        $max = $base + $jitter;

        return random_int($min, max($min + 1, $max));
    }

    protected function extractRateLimitInfo(Response $response): RateLimitInfo
    {
        $retryAfter = $this->getHeaderInt($response, 'X-RateLimit-Retry-After-Seconds')
            ?? $this->getHeaderInt($response, 'Retry-After');
        $retryAt    = $this->getHeaderInt($response, 'X-RateLimit-Retry-At');

        return new RateLimitInfo($retryAfter, $retryAt);
    }

    protected function getHeaderInt(Response $response, string $header): ?int
    {
        $value = $response->header($header);

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function extractPageItems(array $payload): array
    {
        if (array_key_exists('data', $payload) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return $payload;
    }

    protected function resolveNextPage(array $payload, int $currentPage): ?int
    {
        if (isset($payload['meta']['current_page'], $payload['meta']['last_page'])) {
            $current = (int) $payload['meta']['current_page'];
            $last    = (int) $payload['meta']['last_page'];

            return $current < $last ? $current + 1 : null;
        }

        $links = $payload['links'] ?? $payload['Links'] ?? null;
        if (is_array($links)) {
            $hasNext = $links['next'] ?? $links['Next'] ?? null;

            return $hasNext ? $currentPage + 1 : null;
        }

        return null;
    }

    protected function resource(string $key, string $class)
    {
        if (!array_key_exists($key, $this->resources)) {
            $this->resources[$key] = new $class($this);
        }

        return $this->resources[$key];
    }

    protected function normalizePath(string $path): string
    {
        return ltrim($path, '/');
    }

    protected function loadOpenApi(): ?CartrackOpenApi
    {
        $path = base_path('resources/openapi/cartrack_fleet.json');

        if (!is_file($path)) {
            return null;
        }

        return new CartrackOpenApi($path);
    }
}
