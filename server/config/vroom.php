<?php

/**
 * -------------------------------------------
 * Fleetbase Core API Configuration
 * -------------------------------------------
 */
return [
    'api' => [
        'version' => '0.0.1',
        'routing' => [
            'prefix' => 'vroom',
            'internal_prefix' => 'int'
        ],
    ],
    'api_key' => env('VROOM_API_KEY'),
    'base_uri' => env('VROOM_BASE_URI', env('VROOM_HOST', 'https://api.verso-optim.com/vrp/v1')),
    'endpoint_mode' => env('VROOM_ENDPOINT_MODE', 'saas'),
];
