<?php

return [
    'host'      => env('TRIGGER_HOST'),
    'port'      => env('TRIGGER_PORT', 3306),
    'user'      => env('TRIGGER_USER'),
    'password'  => env('TRIGGER_PASSWORD'),
    'databases' => explode(',', env('TRIGGER_DATABASES')),
    'tables'    => explode(',', env('TRIGGER_TABLES')),
    'heartbeat' => env('TRIGGER_HEARTBEAT', 3),
];
