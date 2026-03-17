<?php

return [
    'api_base_url' => env('CONTA_AZUL_API_BASE_URL', 'https://api-v2.contaazul.com'),
    'auth_base_url' => env('CONTA_AZUL_AUTH_BASE_URL', 'https://auth.contaazul.com'),
    'client_id' => env('CONTA_AZUL_CLIENT_ID'),
    'client_secret' => env('CONTA_AZUL_CLIENT_SECRET'),
    'scope' => env('CONTA_AZUL_SCOPE', 'openid profile aws.cognito.signin.user.admin'),
    'redirect_uri' => env('CONTA_AZUL_REDIRECT_URI'),
    'state_ttl_minutes' => (int) env('CONTA_AZUL_STATE_TTL_MINUTES', 10),
    'refresh_leeway_minutes' => (int) env('CONTA_AZUL_REFRESH_LEEWAY_MINUTES', 5),
];
