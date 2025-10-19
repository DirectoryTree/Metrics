<?php

use Carbon\Carbon;
use DirectoryTree\Metrics\Facades\Metrics;
use DirectoryTree\Metrics\Metric;
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\PendingMetric;

beforeEach(function () {
    config(['metrics.queue' => false]);
});

it('can record hourly metrics using pending metric', function () {
    PendingMetric::make('api:requests')
        ->hourly()
        ->record();

    $metric = Metric::first();

    expect($metric->name)->toBe('api:requests')
        ->and($metric->hour)->toBe(now()->hour)
        ->and($metric->value)->toBe(1);
});

it('can record hourly metrics with custom date', function () {
    $datetime = Carbon::parse('2025-10-19 14:30:00');

    PendingMetric::make('api:requests')
        ->date($datetime)
        ->hourly()
        ->record();

    $metric = Metric::first();

    expect($metric->name)->toBe('api:requests')
        ->and($metric->year)->toBe(2025)
        ->and($metric->month)->toBe(10)
        ->and($metric->day)->toBe(19)
        ->and($metric->hour)->toBe(14)
        ->and($metric->value)->toBe(1);
});

it('records daily metrics when hourly is not enabled', function () {
    PendingMetric::make('page_views')->record();

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->hour)->toBeNull()
        ->and($metric->value)->toBe(1);
});

it('can record hourly metrics using MetricData', function () {
    $datetime = Carbon::parse('2025-10-19 15:45:00');

    Metrics::record(new MetricData(
        name: 'api:requests',
        date: $datetime,
        hourly: true
    ));

    $metric = Metric::first();

    expect($metric->name)->toBe('api:requests')
        ->and($metric->hour)->toBe(15)
        ->and($metric->value)->toBe(1);
});

it('increments hourly metrics for the same hour', function () {
    $datetime = Carbon::parse('2025-10-19 14:30:00');

    PendingMetric::make('api:requests')
        ->date($datetime)
        ->hourly()
        ->record();

    PendingMetric::make('api:requests')
        ->date($datetime)
        ->hourly()
        ->record();

    expect(Metric::count())->toBe(1);

    $metric = Metric::first();

    expect($metric->hour)->toBe(14)
        ->and($metric->value)->toBe(2);
});

it('creates separate metrics for different hours', function () {
    $hour1 = Carbon::parse('2025-10-19 14:00:00');
    $hour2 = Carbon::parse('2025-10-19 15:00:00');

    PendingMetric::make('api:requests')
        ->date($hour1)
        ->hourly()
        ->record();

    PendingMetric::make('api:requests')
        ->date($hour2)
        ->hourly()
        ->record();

    expect(Metric::count())->toBe(2);

    $metrics = Metric::orderBy('hour')->get();

    expect($metrics[0]->hour)->toBe(14)
        ->and($metrics[0]->value)->toBe(1)
        ->and($metrics[1]->hour)->toBe(15)
        ->and($metrics[1]->value)->toBe(1);
});

it('can query metrics for this hour', function () {
    $now = now();
    $lastHour = now()->subHour();

    PendingMetric::make('api:requests')
        ->date($now)
        ->hourly()
        ->record(5);

    PendingMetric::make('api:requests')
        ->date($lastHour)
        ->hourly()
        ->record(3);

    $thisHourMetrics = Metric::thisHour()->sum('value');

    expect($thisHourMetrics)->toBe(5);
});

it('can query metrics for last hour', function () {
    $now = now();
    $lastHour = now()->subHour();

    PendingMetric::make('api:requests')
        ->date($now)
        ->hourly()
        ->record(5);

    PendingMetric::make('api:requests')
        ->date($lastHour)
        ->hourly()
        ->record(3);

    $lastHourMetrics = Metric::lastHour()->sum('value');

    expect($lastHourMetrics)->toBe(3);
});

it('can query metrics on a specific hour', function () {
    $datetime = Carbon::parse('2025-10-19 14:30:00');

    PendingMetric::make('api:requests')
        ->date($datetime)
        ->hourly()
        ->record(10);

    $metrics = Metric::onHour($datetime)->sum('value');

    expect($metrics)->toBe(10);
});

it('can query metrics between hours', function () {
    $start = Carbon::parse('2025-10-19 14:00:00');
    $middle = Carbon::parse('2025-10-19 15:00:00');
    $end = Carbon::parse('2025-10-19 16:00:00');

    PendingMetric::make('api:requests')->date($start)->hourly()->record(5);
    PendingMetric::make('api:requests')->date($middle)->hourly()->record(10);
    PendingMetric::make('api:requests')->date($end)->hourly()->record(15);

    $metrics = Metric::betweenHours($start, $middle)->sum('value');

    expect($metrics)->toBe(15);
});

it('treats hourly and daily metrics as separate', function () {
    $datetime = Carbon::parse('2025-10-19 14:30:00');

    PendingMetric::make('page_views')
        ->date($datetime)
        ->record(5);

    PendingMetric::make('page_views')
        ->date($datetime)
        ->hourly()
        ->record(3);

    expect(Metric::count())->toBe(2);

    $dailyMetric = Metric::whereNull('hour')->first();
    $hourlyMetric = Metric::whereNotNull('hour')->first();

    expect($dailyMetric->value)->toBe(5)
        ->and($hourlyMetric->value)->toBe(3)
        ->and($hourlyMetric->hour)->toBe(14);
});

it('can chain hourly with other methods', function () {
    $user = createUser();
    $datetime = Carbon::parse('2025-10-19 14:30:00');

    PendingMetric::make('api:requests')
        ->category('external')
        ->date($datetime)
        ->measurable($user)
        ->hourly()
        ->record(10);

    $metric = Metric::first();

    expect($metric->name)->toBe('api:requests')
        ->and($metric->category)->toBe('external')
        ->and($metric->hour)->toBe(14)
        ->and($metric->measurable_type)->toBe(get_class($user))
        ->and($metric->measurable_id)->toBe($user->id)
        ->and($metric->value)->toBe(10);
});

it('works with capturing mode for hourly metrics', function () {
    $datetime = Carbon::parse('2025-10-19 14:30:00');

    Metrics::capture();

    PendingMetric::make('api:requests')->date($datetime)->hourly()->record(5);
    PendingMetric::make('api:requests')->date($datetime)->hourly()->record(3);

    expect(Metric::count())->toBe(0);

    Metrics::commit();

    expect(Metric::count())->toBe(1);

    $metric = Metric::first();

    expect($metric->value)->toBe(8)
        ->and($metric->hour)->toBe(14);
});
