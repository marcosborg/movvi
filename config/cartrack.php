<?php

return [
    'base_url' => env('CARTRACK_BASE_URL', 'https://fleetapi-pt.cartrack.com'),
    'base_path' => env('CARTRACK_BASE_PATH', '/rest'),
    'username' => env('CARTRACK_USERNAME'),
    'password' => env('CARTRACK_PASSWORD'),
    'timeout' => env('CARTRACK_TIMEOUT', 30),
    'max_attempts' => env('CARTRACK_MAX_ATTEMPTS', 4),
    'backoff_initial_ms' => env('CARTRACK_BACKOFF_INITIAL_MS', 500),
    'backoff_max_ms' => env('CARTRACK_BACKOFF_MAX_MS', 10000),
    'backoff_jitter' => env('CARTRACK_BACKOFF_JITTER', 0.25),
    'default_accept' => 'application/json',
    'aemp_accept' => env('CARTRACK_AEMP_ACCEPT', 'application/iso15143-snapshot+json'),
    'headers' => [],
];
