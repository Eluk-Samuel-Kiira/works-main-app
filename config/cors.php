<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login-register/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://127.0.0.1:8001',
        'http://localhost:8001',
        'http://127.0.0.1:8000',
        'http://localhost:8000',
        'https://stardenaworks.com/',
        'https://ma1n.stardenaworks.com/',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];