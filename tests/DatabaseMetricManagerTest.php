<?php

use Carbon\CarbonImmutable;
use DirectoryTree\Metrics\Facades\Metrics;
use DirectoryTree\Metrics\Jobs\CommitMetrics;
use DirectoryTree\Metrics\Jobs\RecordMetric;
use DirectoryTree\Metrics\Metric;
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\Tests\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('can record a metric without queueing', function () {
    config(['metrics.queue' => false]);

    $metric = Metrics::record(new MetricData('page_views'));

    expect($metric)->toBeInstanceOf(Metric::class)
        ->and($metric->name)->toBe('page_views')
        ->and($metric->value)->toBe(1)
        ->and(Metric::count())->toBe(1);

    Queue::assertNothingPushed();
});

it('can record a metric with queueing enabled', function () {
    config(['metrics.queue' => true]);

    $metric = Metrics::record(new MetricData('page_views'));

    expect($metric)->toBeNull();

    Queue::assertPushed(RecordMetric::class);
});

it('can record multiple metrics with the same name and increments value', function () {
    config(['metrics.queue' => false]);

    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('page_views', value: 3));

    expect(Metric::count())->toBe(1);

    $metric = Metric::first();
    expect($metric->name)->toBe('page_views')
        ->and($metric->value)->toBe(5);
});

it('can record metrics with different names', function () {
    config(['metrics.queue' => false]);

    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('api_calls'));

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('name', 'page_views')->first()->value)->toBe(1)
        ->and(Metric::where('name', 'api_calls')->first()->value)->toBe(1);
});

it('can record metrics with different categories', function () {
    config(['metrics.queue' => false]);

    Metrics::record(new MetricData('page_views', 'marketing'));
    Metrics::record(new MetricData('page_views', 'analytics'));

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('category', 'marketing')->first()->value)->toBe(1)
        ->and(Metric::where('category', 'analytics')->first()->value)->toBe(1);
});

it('can record metrics with same name and category and increments value', function () {
    config(['metrics.queue' => false]);

    Metrics::record(new MetricData('page_views', 'marketing'));
    Metrics::record(new MetricData('page_views', 'marketing'));

    expect(Metric::count())->toBe(1);

    $metric = Metric::first();
    expect($metric->name)->toBe('page_views')
        ->and($metric->category)->toBe('marketing')
        ->and($metric->value)->toBe(2);
});

it('can record metrics with different dates', function () {
    config(['metrics.queue' => false]);

    $today = CarbonImmutable::parse('2025-01-15');
    $yesterday = CarbonImmutable::parse('2025-01-14');

    Metrics::record(new MetricData('page_views', date: $today));
    Metrics::record(new MetricData('page_views', date: $yesterday));

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('day', 15)->first()->value)->toBe(1)
        ->and(Metric::where('day', 14)->first()->value)->toBe(1);
});

it('can record metrics with measurable models', function () {
    config(['metrics.queue' => false]);

    $user1 = User::forceCreate(['name' => 'John', 'email' => 'john@example.com', 'password' => 'password']);
    $user2 = User::forceCreate(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'password']);

    Metrics::record(new MetricData('logins', measurable: $user1));
    Metrics::record(new MetricData('logins', measurable: $user2));

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('measurable_id', $user1->id)->first()->value)->toBe(1)
        ->and(Metric::where('measurable_id', $user2->id)->first()->value)->toBe(1);
});

it('can start capturing metrics', function () {
    config(['metrics.queue' => false]);

    expect(Metrics::isCapturing())->toBeFalse();

    Metrics::capture();

    expect(Metrics::isCapturing())->toBeTrue();
});

it('can stop capturing metrics', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    expect(Metrics::isCapturing())->toBeTrue();

    Metrics::stopCapturing();
    expect(Metrics::isCapturing())->toBeFalse();
});

it('adds metrics to repository when capturing', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('api_calls'));

    expect(Metric::count())->toBe(0);

    Queue::assertNothingPushed();
});

it('commits captured metrics to database without queueing', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('api_calls'));

    expect(Metric::count())->toBe(0);

    Metrics::commit();

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('name', 'page_views')->first()->value)->toBe(2)
        ->and(Metric::where('name', 'api_calls')->first()->value)->toBe(1);

    Queue::assertNothingPushed();
});

it('commits captured metrics to queue when queueing enabled', function () {
    config(['metrics.queue' => true]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('api_calls'));

    Metrics::commit();

    expect(Metric::count())->toBe(0);

    Queue::assertPushed(CommitMetrics::class);
});

it('flushes repository after committing', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));

    Metrics::commit();

    expect(Metric::count())->toBe(1);

    Metrics::commit();

    expect(Metric::count())->toBe(1);
});

it('does not commit when repository is empty', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::commit();

    expect(Metric::count())->toBe(0);

    Queue::assertNothingPushed();
});

it('groups captured metrics by name, category, date, and measurable', function () {
    config(['metrics.queue' => false]);

    $user = User::forceCreate(['name' => 'John', 'email' => 'john@example.com', 'password' => 'password']);
    $date = CarbonImmutable::parse('2025-01-15');

    Metrics::capture();
    Metrics::record(new MetricData('page_views', 'marketing', value: 1, date: $date));
    Metrics::record(new MetricData('page_views', 'marketing', value: 2, date: $date));
    Metrics::record(new MetricData('page_views', 'analytics', value: 3, date: $date));
    Metrics::record(new MetricData('logins', measurable: $user));

    Metrics::commit();

    expect(Metric::count())->toBe(3)
        ->and(Metric::where('name', 'page_views')->where('category', 'marketing')->first()->value)->toBe(3)
        ->and(Metric::where('name', 'page_views')->where('category', 'analytics')->first()->value)->toBe(3)
        ->and(Metric::where('name', 'logins')->first()->value)->toBe(1);
});

it('can capture, commit, and continue recording', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));
    Metrics::commit();

    expect(Metric::count())->toBe(1);

    Metrics::record(new MetricData('api_calls'));

    expect(Metric::count())->toBe(1);

    Metrics::commit();

    expect(Metric::count())->toBe(2);
});

it('stores date components correctly', function () {
    config(['metrics.queue' => false]);

    $date = CarbonImmutable::parse('2025-03-15');

    Metrics::record(new MetricData('page_views', date: $date));

    $metric = Metric::first();
    expect($metric->year)->toBe(2025)
        ->and($metric->month)->toBe(3)
        ->and($metric->day)->toBe(15);
});
