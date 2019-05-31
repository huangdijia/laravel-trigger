<?php

return [
    'host'       => env('TRIGGER_HOST', ''),
    'port'       => env('TRIGGER_PORT', 3306),
    'user'       => env('TRIGGER_USER', ''),
    'password'   => env('TRIGGER_PASSWORD', ''),
    'databases'  => env('TRIGGER_DATABASES', '') ? explode(',', env('TRIGGER_DATABASES')) : [],
    'tables'     => env('TRIGGER_TABLES', '') ? explode(',', env('TRIGGER_TABLES')) : [],
    'heartbeat'  => (int) env('TRIGGER_HEARTBEAT', 3),
    'event_path' => app()->basePath('app/Events'),
];
