<?php

use DirectoryTree\Metrics\Commands\CommitMetrics;
use DirectoryTree\Metrics\Facades\Metrics;
use DirectoryTree\Metrics\Jobs\CommitMetrics as CommitMetricsJob;
use DirectoryTree\Metrics\Metric;
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\MetricRepository;
use DirectoryTree\Metrics\RedisMetricRepository;
use DirectoryTree\Metrics\Tests\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Queue::fake();
    Redis::flushdb();
});

it('displays message when no metrics to commit', function () {
    $this->artisan(CommitMetrics::class)
        ->expectsOutput('No metrics to commit.')
        ->assertSuccessful();

    expect(Metric::count())->toBe(0);
});

it('commits captured metrics without queueing', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('api_calls'));

    expect(Metric::count())->toBe(0);

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 2 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('name', 'page_views')->first()->value)->toBe(2)
        ->and(Metric::where('name', 'api_calls')->first()->value)->toBe(1);

    Queue::assertNothingPushed();
});

it('commits captured metrics with queueing enabled', function () {
    config(['metrics.queue' => true]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('api_calls'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 2 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(0);

    Queue::assertPushed(CommitMetricsJob::class);
});

it('displays singular message for one metric', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 1 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(1);
});

it('displays plural message for multiple metrics', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('api_calls'));
    Metrics::record(new MetricData('logins'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 3 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(3);
});

it('flushes repository after committing', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));

    $this->artisan(CommitMetrics::class)
        ->assertSuccessful();

    expect(Metric::count())->toBe(1);

    // Running again should show no metrics
    $this->artisan(CommitMetrics::class)
        ->expectsOutput('No metrics to commit.')
        ->assertSuccessful();

    expect(Metric::count())->toBe(1);
});

it('commits metrics with categories', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views', 'marketing'));
    Metrics::record(new MetricData('page_views', 'analytics'));
    Metrics::record(new MetricData('api_calls'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 3 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(3)
        ->and(Metric::where('category', 'marketing')->count())->toBe(1)
        ->and(Metric::where('category', 'analytics')->count())->toBe(1)
        ->and(Metric::whereNull('category')->count())->toBe(1);
});

it('commits metrics with measurable models', function () {
    config(['metrics.queue' => false]);

    $user1 = createUser(['name' => 'John', 'email' => 'john@example.com']);
    $user2 = createUser(['name' => 'Jane', 'email' => 'jane@example.com']);

    Metrics::capture();
    Metrics::record(new MetricData('logins', measurable: $user1));
    Metrics::record(new MetricData('logins', measurable: $user2));
    Metrics::record(new MetricData('logins'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 3 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(3)
        ->and(Metric::where('measurable_id', $user1->id)->count())->toBe(1)
        ->and(Metric::where('measurable_id', $user2->id)->count())->toBe(1)
        ->and(Metric::whereNull('measurable_id')->count())->toBe(1);
});

it('commits large number of metrics', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();

    for ($i = 0; $i < 100; $i++) {
        Metrics::record(new MetricData('page_views'));
    }

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 1 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->value)->toBe(100);
});

it('can be run multiple times', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('page_views'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 1 metric(s).')
        ->assertSuccessful();

    Metrics::record(new MetricData('api_calls'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 1 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(2);
});

it('works when capturing is not enabled', function () {
    config(['metrics.queue' => false]);

    // Record without capturing
    Metrics::record(new MetricData('page_views'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('No metrics to commit.')
        ->assertSuccessful();

    // Metric should be recorded directly
    expect(Metric::count())->toBe(1);
});

it('commits metrics with custom values', function () {
    config(['metrics.queue' => false]);

    Metrics::capture();
    Metrics::record(new MetricData('revenue', value: 100));
    Metrics::record(new MetricData('revenue', value: 250));
    Metrics::record(new MetricData('revenue', value: 50));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 1 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->value)->toBe(400);
});

it('commits metrics with different dates separately', function () {
    config(['metrics.queue' => false]);

    $today = now();
    $yesterday = now()->subDay();

    Metrics::capture();
    Metrics::record(new MetricData('page_views', date: $today));
    Metrics::record(new MetricData('page_views', date: $yesterday));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 2 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(2);
});

it('handles metrics with all properties', function () {
    config(['metrics.queue' => false]);

    $user = createUser();
    $date = now();

    Metrics::capture();
    Metrics::record(new MetricData('page_views', 'marketing', value: 5, date: $date, measurable: $user));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 1 metric(s).')
        ->assertSuccessful();

    $metric = Metric::first();

    expect($metric->name)->toBe('page_views')
        ->and($metric->category)->toBe('marketing')
        ->and($metric->value)->toBe(5)
        ->and($metric->measurable_id)->toBe($user->id)
        ->and($metric->measurable_type)->toBe(User::class);
});

it('works with redis repository', function () {
    config(['metrics.queue' => false]);

    // Bind Redis repository
    $this->app->singleton(
        MetricRepository::class,
        RedisMetricRepository::class
    );

    Metrics::capture();

    Metrics::record(new MetricData('page_views'));
    Metrics::record(new MetricData('api_calls'));

    $this->artisan(CommitMetrics::class)
        ->expectsOutput('Committed 2 metric(s).')
        ->assertSuccessful();

    expect(Metric::count())->toBe(2);
});
