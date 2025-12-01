<?php

namespace App\Services\Cartrack;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class CartrackClientConfig
{
    public string $baseUrl;
    public string $basePath = '/rest';
    public string $username;
    public string $password;
    public int $timeoutSeconds = 30;
    public int $maxAttempts = 4;
    public int $initialBackoffMs = 500;
    public int $maxBackoffMs = 10000;
    public float $jitterFactor = 0.25;
    public string $defaultAccept = 'application/json';
    public string $aempAccept = 'application/iso15143-snapshot+json';
    public array $defaultHeaders = [];

    public static function fromConfig(array $config): self
    {
        $self = new self();

        $self->baseUrl         = rtrim((string) Arr::get($config, 'base_url', ''), '/');
        $self->basePath        = Arr::get($config, 'base_path', '/rest') ?: '/rest';
        $self->username        = (string) Arr::get($config, 'username');
        $self->password        = (string) Arr::get($config, 'password');
        $self->timeoutSeconds  = (int) Arr::get($config, 'timeout', 30);
        $self->maxAttempts     = max(1, (int) Arr::get($config, 'max_attempts', 4));
        $self->initialBackoffMs = max(50, (int) Arr::get($config, 'backoff_initial_ms', 500));
        $self->maxBackoffMs    = max($self->initialBackoffMs, (int) Arr::get($config, 'backoff_max_ms', 10000));
        $self->jitterFactor    = (float) Arr::get($config, 'backoff_jitter', 0.25);
        $self->defaultAccept   = (string) Arr::get($config, 'default_accept', 'application/json');
        $self->aempAccept      = (string) Arr::get($config, 'aemp_accept', 'application/iso15143-snapshot+json');
        $self->defaultHeaders  = (array) Arr::get($config, 'headers', []);
        $self->basePath        = '/' . ltrim($self->basePath, '/');

        if ($self->baseUrl === '' || !str_starts_with($self->baseUrl, 'https://')) {
            throw new InvalidArgumentException('Cartrack base_url deve ser HTTPS e não pode ser vazio.');
        }

        if ($self->username === '' || $self->password === '') {
            throw new InvalidArgumentException('Cartrack username e password são obrigatórios.');
        }

        return $self;
    }
}
