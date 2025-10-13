<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Queue Metric Recording
    |--------------------------------------------------------------------------
    |
    | When enabled, recorded metrics will be dispatched in the queued job to
    | be saved. This is useful for high-traffic applications where recording
    | a large number of metrics could impact performance. When disabled,
    | metrics will be recorded synchronously.
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
    | When enabled, metrics will be automatically committed when the application
    | terminates. This is useful for capturing metrics in a request-response
    | cycle. You can disable this and manually commit metrics when needed.
    |
    */

    'auto_commit' => env('METRICS_AUTO_COMMIT', true),

    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | When using the RedisMetricRepository, you can configure which Redis
    | connection to use and the key name for storing pending metrics.
    |
    */

    'redis' => [
        'connection' => env('METRICS_REDIS_CONNECTION'),
        'key' => env('METRICS_REDIS_KEY', 'metrics:pending'),
    ],
];
