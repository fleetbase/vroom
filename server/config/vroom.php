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
    'api_key' => env('VROOM_API_KEY')
];
