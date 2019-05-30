<?php

return [
    'host'      => env('TRIGGER_HOST', ''),
    'port'      => env('TRIGGER_PORT', 3306),
    'user'      => env('TRIGGER_USER', ''),
    'password'  => env('TRIGGER_PASSWORD', ''),
    'databases' => env('TRIGGER_DATABASES', '') ? explode(',', env('TRIGGER_DATABASES')) : [],
    'tables'    => env('TRIGGER_TABLES', '') ? explode(',', env('TRIGGER_TABLES')) : [],
    'heartbeat' => env('TRIGGER_HEARTBEAT', 3),

    'cache_key'  => config('app.name', 'trigger') . ':trigger:replication',
    'event_path' => app()->basePath('app/Events'),
];
