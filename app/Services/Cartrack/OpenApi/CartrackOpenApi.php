<?php

namespace App\Services\Cartrack\OpenApi;

class CartrackOpenApi
{
    protected array $spec;

    public function __construct(string $path)
    {
        $contents = @file_get_contents($path);
        $this->spec = $contents ? json_decode($contents, true) ?: [] : [];
    }

    public function definition(string $name): ?array
    {
        return $this->spec['definitions'][$name] ?? null;
    }

    public function basePath(): ?string
    {
        return $this->spec['basePath'] ?? null;
    }

    public function parametersFor(string $path, string $method): array
    {
        $method = strtolower($method);
        $parameters = $this->spec['paths'][$path][$method]['parameters'] ?? [];

        return is_array($parameters) ? $parameters : [];
    }

    public function filterQueryParams(string $path, string $method, array $query): array
    {
        $allowed = [];
        foreach ($this->parametersFor($path, $method) as $param) {
            if (($param['in'] ?? '') === 'query' && isset($param['name'])) {
                $allowed[] = $param['name'];
            }
        }

        if (empty($allowed)) {
            return $query;
        }

        return array_intersect_key($query, array_flip($allowed));
    }
}
