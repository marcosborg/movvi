<?php

return [
    'enabled' => env('MAINTENANCE_INTEGRATION_ENABLED', false),
    'production_enabled' => env('MAINTENANCE_INTEGRATION_PRODUCTION_ENABLED', false),
    'production_writes_enabled' => env('MAINTENANCE_INTEGRATION_PRODUCTION_WRITES_ENABLED', false),
    'company_id' => env('MAINTENANCE_INTEGRATION_COMPANY_ID'),
    'token_hash' => env('MAINTENANCE_INTEGRATION_TOKEN_HASH'),
    'expires_at' => env('MAINTENANCE_INTEGRATION_EXPIRES_AT'),
    'allowed_ip' => env('MAINTENANCE_INTEGRATION_ALLOWED_IP'),
];
