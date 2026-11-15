<?php

declare(strict_types=1);
/**
 * This file is part of huangdijia/laravel-trigger.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */
return [
    'default' => 'default',

    'replications' => [
        'default' => [
            'host' => env('TRIGGER_HOST', ''),
            'port' => env('TRIGGER_PORT', 3306),
            'user' => env('TRIGGER_USER', ''),
            'password' => env('TRIGGER_PASSWORD', ''),

            // detect from trigger routers
            'detect' => (bool) env('TRIGGER_DETECT', false),
            // or set database and tables
            'databases' => env('TRIGGER_DATABASES', '') ? explode(',', env('TRIGGER_DATABASES')) : [],
            'tables' => env('TRIGGER_TABLES', '') ? explode(',', env('TRIGGER_TABLES')) : [],

            'heartbeat' => (int) env('TRIGGER_HEARTBEAT', 3),

            // Periodically ping the MySQL metadata connection to avoid server-side idle disconnects.
            // Set to 0 to disable.
            'keepalive' => (int) env('TRIGGER_KEEPALIVE', 0),

            // Remember the resume position at transaction boundaries on a busy stream at most once
            // per this many seconds (heartbeats only arrive while the binlog is idle).
            // Set to 0 to remember it on heartbeats only (previous behavior).
            'checkpoint_interval' => (int) env('TRIGGER_CHECKPOINT_INTERVAL', 5),

            // How long a remembered resume position is kept, in seconds (was one hour); 0 keeps it forever.
            'checkpoint_ttl' => (int) env('TRIGGER_CHECKPOINT_TTL', 86400),

            // MySQL session variables to apply on connect (for the metadata connection).
            // Example:
            // - wait_timeout=7200,interactive_timeout=7200
            'session_variables' => env('TRIGGER_SESSION_VARIABLES', '')
                ? array_filter(array_map('trim', explode(',', (string) env('TRIGGER_SESSION_VARIABLES'))))
                : [],
            'subscribers' => [
                // Huangdijia\Trigger\Subscribers\Heartbeat::class,
            ],
            'route' => app()->basePath('routes/trigger.php'),
        ],
    ],
];
