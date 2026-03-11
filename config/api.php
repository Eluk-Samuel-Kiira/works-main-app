<?php
// config/api.php

return [
    /*
    |--------------------------------------------------------------------------
    | API URLs Configuration
    |--------------------------------------------------------------------------
    |
    | Define all the API endpoints for your applications here.
    | Change only this file when moving to production.
    |
    */

    // For works-main app
    'main_app' => [
        'url' => env('MAIN_APP_URL', 'http://127.0.0.1:8000'),
        'api_base' => env('MAIN_APP_URL', 'http://127.0.0.1:8000') . '/api',
    ],
    
    // For works-web app
    'web_app' => [
        'url' => env('WEB_APP_URL', 'http://127.0.0.1:8001'),
        'api_base' => env('WEB_APP_URL', 'http://127.0.0.1:8001') . '/api',
    ],

    
];