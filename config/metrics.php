<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Metric Repository Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines which driver will be used to store
    | captured metrics. The "array" driver stores metrics in memory, while
    | the "redis" driver stores metrics in Redis for distributed systems.
    |
    */

    'driver' => 'array',

    /*
    |--------------------------------------------------------------------------
    | Queue Metric Recording
    |--------------------------------------------------------------------------
    |
    | This option determines whether metric recording jobs should be queued
    | instead of being processed synchronously. Queuing is recommended for
    | high-traffic applications to improve performance and reduce latency.
    |
    */

    'queue' => env('METRICS_QUEUE', false) ? [
        'name' => env('METRICS_QUEUE_NAME'),
        'connection' => env('METRICS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    ] : false,

    /*
    |--------------------------------------------------------------------------
    | Auto-Commit Metrics
    |--------------------------------------------------------------------------
    |
    | This configuration option determines whether metrics will be committed
    | automatically when the application terminates. You may disable this
    | option if you prefer to manually commit metrics at specific times.
    |
    */

    'auto_commit' => env('METRICS_AUTO_COMMIT', true),

    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the Redis connection, key, and
    | TTL for storing pending metrics when using the "redis" driver. The
    | key stores metrics in a hash with the specified time-to-live value.
    |
    */

    'redis' => [
        'connection' => env('METRICS_REDIS_CONNECTION'),
        'key' => env('METRICS_REDIS_KEY', 'metrics:pending'),
        'ttl' => env('METRICS_REDIS_TTL', 86400), // 1 day
    ],
];
