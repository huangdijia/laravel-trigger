<?php

declare(strict_types=1);
/**
 * This file is part of huangdijia/laravel-trigger.
 *
 * @link     https://github.com/huangdijia/laravel-trigger
 * @document https://github.com/huangdijia/laravel-trigger/blob/4.x/README.md
 * @contact  huangdijia@gmail.com
 */
$sessionVariables = [];
$sessionVariablesRaw = (string) env('TRIGGER_SESSION_VARIABLES', '');

if (trim($sessionVariablesRaw) !== '') {
    foreach (explode(',', trim($sessionVariablesRaw)) as $pair) {
        $pair = trim($pair);

        if ($pair === '' || ! str_contains($pair, '=')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $pair, 2));

        if ($name === '' || $value === '') {
            continue;
        }

        if (is_numeric($value)) {
            $value = (int) $value;
        }

        $sessionVariables[$name] = $value;
    }
}

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

            // MySQL session variables to apply on connect (for the metadata connection).
            // Example:
            // - wait_timeout=7200,interactive_timeout=7200
            'session_variables' => $sessionVariables,
            'subscribers' => [
                // Huangdijia\Trigger\Subscribers\Heartbeat::class,
            ],
            'route' => app()->basePath('routes/trigger.php'),
        ],
    ],
];
