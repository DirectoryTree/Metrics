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
];
