<?php

use Carbon\Carbon;
use DirectoryTree\Metrics\Facades\Metrics;
use DirectoryTree\Metrics\Jobs\RecordMetric;
use DirectoryTree\Metrics\Metric;
use DirectoryTree\Metrics\PendingMetric;
use DirectoryTree\Metrics\Tests\User;

it('can be instantiated with constructor', function () {
    $pending = new PendingMetric('page_views');

    expect($pending)->toBeInstanceOf(PendingMetric::class);
});

it('can be instantiated with make method', function () {
    $pending = PendingMetric::make('page_views');

    expect($pending)->toBeInstanceOf(PendingMetric::class);
});

it('can set category', function () {
    $pending = PendingMetric::make('page_views')->category('marketing');

    $data = $pending->toMetricData(1);

    expect($data->name())->toBe('page_views')
        ->and($data->category())->toBe('marketing')
        ->and($data->value())->toBe(1);
});

it('can set date', function () {
    $date = Carbon::yesterday();

    $pending = PendingMetric::make('page_views')->date($date);

    $data = $pending->toMetricData(1);

    expect($data->name())->toBe('page_views')
        ->and($data->year())->toBe($date->year)
        ->and($data->month())->toBe($date->month)
        ->and($data->day())->toBe($date->day);
});

it('can set measurable', function () {
    $user = createUser();

    $pending = PendingMetric::make('logins')->measurable($user);

    $data = $pending->toMetricData(1);

    expect($data->name())->toBe('logins')
        ->and($data->measurable())->toBe($user);
});

it('can chain multiple setters', function () {
    $user = createUser();

    $date = Carbon::yesterday();

    $pending = PendingMetric::make('page_views')
        ->category('marketing')
        ->date($date)
        ->measurable($user);

    $data = $pending->toMetricData(5);

    expect($data->name())->toBe('page_views')
        ->and($data->category())->toBe('marketing')
        ->and($data->value())->toBe(5)
        ->and($data->year())->toBe($date->year)
        ->and($data->month())->toBe($date->month)
        ->and($data->day())->toBe($date->day)
        ->and($data->measurable())->toBe($user);
});

it('records metric with default value', function () {
    PendingMetric::make('page_views')->record();

    expect(Metric::count())->toBe(1);

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->value)->toBe(1);
});

it('records metric with custom value', function () {
    PendingMetric::make('page_views')->record(5);

    expect(Metric::count())->toBe(1);

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->value)->toBe(5);
});

it('records metric with category', function () {
    PendingMetric::make('page_views')
        ->category('marketing')
        ->record();

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->category)->toBe('marketing')
        ->and($metric->value)->toBe(1);
});

it('records metric with custom date', function () {
    $date = Carbon::parse('2025-03-15');

    PendingMetric::make('page_views')
        ->date($date)
        ->record();

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->year)->toBe(2025)
        ->and($metric->month)->toBe(3)
        ->and($metric->day)->toBe(15);
});

it('records metric with measurable model', function () {
    $user = createUser();

    PendingMetric::make('logins')
        ->measurable($user)
        ->record();

    $metric = Metric::first();

    expect($metric->name)->toBe('logins')
        ->and($metric->measurable_type)->toBe(User::class)
        ->and($metric->measurable_id)->toBe($user->id)
        ->and($metric->value)->toBe(1);
});

it('records metric with all properties', function () {
    $user = createUser();

    $date = Carbon::parse('2025-03-15');

    PendingMetric::make('page_views')
        ->category('marketing')
        ->date($date)
        ->measurable($user)
        ->record(10);

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->category)->toBe('marketing')
        ->and($metric->year)->toBe(2025)
        ->and($metric->month)->toBe(3)
        ->and($metric->day)->toBe(15)
        ->and($metric->measurable_type)->toBe(User::class)
        ->and($metric->measurable_id)->toBe($user->id)
        ->and($metric->value)->toBe(10);
});

it('can set null category', function () {
    PendingMetric::make('page_views')
        ->category(null)
        ->record();

    $metric = Metric::first();

    expect($metric->category)->toBeNull();
});

it('records multiple metrics independently', function () {
    PendingMetric::make('page_views')->record();
    PendingMetric::make('api_calls')->record();
    PendingMetric::make('errors')->record();

    expect(Metric::count())->toBe(3);
});

it('increments existing metric when recorded multiple times', function () {
    PendingMetric::make('page_views')->record();
    PendingMetric::make('page_views')->record();
    PendingMetric::make('page_views')->record();

    expect(Metric::count())->toBe(1);

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->value)->toBe(3);
});

it('treats metrics with different categories as separate', function () {
    PendingMetric::make('page_views')->category('marketing')->record();
    PendingMetric::make('page_views')->category('analytics')->record();

    expect(Metric::count())->toBe(2);
});

it('works with capturing mode', function () {
    Metrics::capture();

    PendingMetric::make('page_views')->record();
    PendingMetric::make('api_calls')->record();

    expect(Metric::count())->toBe(0);

    Metrics::commit();

    expect(Metric::count())->toBe(2);
});

it('works with queueing enabled', function () {
    config(['metrics.queue' => true]);

    Queue::fake();

    PendingMetric::make('page_views')->record();

    Queue::assertPushed(RecordMetric::class);
});

it('can be used with helper function', function () {
    $pending = metric('page_views');

    expect($pending)->toBeInstanceOf(PendingMetric::class);

    $pending->record();

    expect(Metric::count())->toBe(1);
});

it('helper function works with full chain', function () {
    $user = createUser();

    metric('page_views')
        ->category('marketing')
        ->measurable($user)
        ->record(5);

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->category)->toBe('marketing')
        ->and($metric->measurable_id)->toBe($user->id)
        ->and($metric->value)->toBe(5);
});

it('can record large values', function () {
    PendingMetric::make('api_calls')->record(1000000);

    $metric = Metric::first();

    expect($metric->value)->toBe(1000000);
});

it('can record zero value', function () {
    PendingMetric::make('errors')->record(0);

    $metric = Metric::first();

    expect($metric->value)->toBe(0);
});

it('creates new instance for each make call', function () {
    $pending1 = PendingMetric::make('page_views')->category('marketing');
    $pending2 = PendingMetric::make('page_views')->category('analytics');

    expect($pending1)->not->toBe($pending2);
});

it('does not mutate when chaining', function () {
    $pending = PendingMetric::make('page_views');

    $withCategory = $pending->category('marketing');

    expect($pending)->toBe($withCategory);
});
