<?php

use Carbon\CarbonImmutable;
use DirectoryTree\Metrics\Jobs\CommitMetrics;
use DirectoryTree\Metrics\Jobs\RecordMetric;
use DirectoryTree\Metrics\Metric;
use DirectoryTree\Metrics\MetricData;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('commits a single metric without queueing', function () {
    $metrics = [new MetricData('page_views')];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->name)->toBe('page_views')
        ->and(Metric::first()->value)->toBe(1);

    Queue::assertNothingPushed();
});

it('commits multiple different metrics without queueing', function () {
    $metrics = [
        new MetricData('page_views'),
        new MetricData('api_calls'),
        new MetricData('errors'),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(3)
        ->and(Metric::where('name', 'page_views')->exists())->toBeTrue()
        ->and(Metric::where('name', 'api_calls')->exists())->toBeTrue()
        ->and(Metric::where('name', 'errors')->exists())->toBeTrue();

    Queue::assertNothingPushed();
});

it('groups metrics by name and sums values', function () {
    $metrics = [
        new MetricData('page_views', value: 5),
        new MetricData('page_views', value: 3),
        new MetricData('page_views', value: 2),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->name)->toBe('page_views')
        ->and(Metric::first()->value)->toBe(10); // 5 + 3 + 2
});

it('groups metrics by category', function () {
    $metrics = [
        new MetricData('page_views', 'marketing', value: 5),
        new MetricData('page_views', 'marketing', value: 3),
        new MetricData('page_views', 'analytics', value: 2),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('category', 'marketing')->first()->value)->toBe(8) // 5 + 3
        ->and(Metric::where('category', 'analytics')->first()->value)->toBe(2);
});

it('groups metrics by date', function () {
    $today = CarbonImmutable::parse('2025-01-15');
    $yesterday = CarbonImmutable::parse('2025-01-14');

    $metrics = [
        new MetricData('page_views', value: 5, date: $today),
        new MetricData('page_views', value: 3, date: $today),
        new MetricData('page_views', value: 2, date: $yesterday),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('day', 15)->first()->value)->toBe(8) // 5 + 3
        ->and(Metric::where('day', 14)->first()->value)->toBe(2);
});

it('groups metrics by measurable model', function () {
    $user1 = createUser(['name' => 'John', 'email' => 'john@example.com']);
    $user2 = createUser(['name' => 'Jane', 'email' => 'jane@example.com']);

    $metrics = [
        new MetricData('logins', value: 5, measurable: $user1),
        new MetricData('logins', value: 3, measurable: $user1),
        new MetricData('logins', value: 2, measurable: $user2),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('measurable_id', $user1->id)->first()->value)->toBe(8) // 5 + 3
        ->and(Metric::where('measurable_id', $user2->id)->first()->value)->toBe(2);
});

it('groups metrics by all unique attributes', function () {
    $user = createUser();

    $date = CarbonImmutable::parse('2025-01-15');

    $metrics = [
        new MetricData('page_views', 'marketing', value: 1, date: $date),
        new MetricData('page_views', 'marketing', value: 2, date: $date),
        new MetricData('page_views', 'analytics', value: 3, date: $date),
        new MetricData('logins', measurable: $user),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(3)
        ->and(Metric::where('name', 'page_views')->where('category', 'marketing')->first()->value)->toBe(3) // 1 + 2
        ->and(Metric::where('name', 'page_views')->where('category', 'analytics')->first()->value)->toBe(3)
        ->and(Metric::where('name', 'logins')->first()->value)->toBe(1);
});

it('dispatches jobs when job is set', function () {
    $metrics = [
        new MetricData('page_views'),
        new MetricData('api_calls'),
    ];

    (new CommitMetrics($metrics))
        ->setJob(mock(Job::class))
        ->handle();

    expect(Metric::count())->toBe(0);

    Queue::assertPushed(RecordMetric::class, 2); // One for each unique metric
});

it('dispatches grouped jobs when job is set', function () {
    $metrics = [
        new MetricData('page_views', value: 5),
        new MetricData('page_views', value: 3),
        new MetricData('api_calls', value: 2),
    ];

    (new CommitMetrics($metrics))
        ->setJob(mock(Job::class))
        ->handle();

    Queue::assertPushed(RecordMetric::class, 2); // One for page_views, one for api_calls
});

it('handles empty metrics array', function () {
    (new CommitMetrics([]))->handle();

    expect(Metric::count())->toBe(0);

    Queue::assertNothingPushed();
});

it('handles metrics with null category', function () {
    $metrics = [
        new MetricData('page_views', null, value: 5),
        new MetricData('page_views', null, value: 3),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->category)->toBeNull()
        ->and(Metric::first()->value)->toBe(8);
});

it('handles metrics with null measurable', function () {
    $metrics = [
        new MetricData('page_views', value: 5),
        new MetricData('page_views', value: 3),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->measurable_type)->toBeNull()
        ->and(Metric::first()->measurable_id)->toBeNull()
        ->and(Metric::first()->value)->toBe(8);
});

it('separates metrics with same name but different categories', function () {
    $metrics = [
        new MetricData('page_views', 'marketing'),
        new MetricData('page_views', 'analytics'),
        new MetricData('page_views', null),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(3);
});

it('commits large number of metrics efficiently', function () {
    $metrics = [];

    for ($i = 0; $i < 100; $i++) {
        $metrics[] = new MetricData('page_views', value: 1);
    }

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->value)->toBe(100);
});

it('commits metrics with different names separately', function () {
    $metrics = [];
    for ($i = 0; $i < 10; $i++) {
        $metrics[] = new MetricData("metric_{$i}");
    }

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(10);
});

it('groups by year, month, and day correctly', function () {
    $date1 = CarbonImmutable::parse('2025-01-15');
    $date2 = CarbonImmutable::parse('2025-01-16');
    $date3 = CarbonImmutable::parse('2025-02-15');

    $metrics = [
        new MetricData('page_views', value: 1, date: $date1),
        new MetricData('page_views', value: 2, date: $date1),
        new MetricData('page_views', value: 3, date: $date2),
        new MetricData('page_views', value: 4, date: $date3),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(3)
        ->and(Metric::where('year', 2025)->where('month', 1)->where('day', 15)->first()->value)->toBe(3) // 1 + 2
        ->and(Metric::where('year', 2025)->where('month', 1)->where('day', 16)->first()->value)->toBe(3)
        ->and(Metric::where('year', 2025)->where('month', 2)->where('day', 15)->first()->value)->toBe(4);
});

it('passes collection to RecordMetric job', function () {
    $metrics = [
        new MetricData('page_views', value: 5),
        new MetricData('page_views', value: 3),
    ];

    (new CommitMetrics($metrics))
        ->setJob(mock(Job::class))
        ->handle();

    Queue::assertPushed(RecordMetric::class, function ($job) {
        return $job->metrics instanceof Collection
            && $job->metrics->count() === 2;
    });
});

it('handles mixed metric types in same commit', function () {
    $user = createUser();

    $date = CarbonImmutable::parse('2025-01-15');

    $metrics = [
        new MetricData('page_views'),
        new MetricData('page_views', 'marketing'),
        new MetricData('page_views', date: $date),
        new MetricData('logins', measurable: $user),
    ];

    (new CommitMetrics($metrics))->handle();

    expect(Metric::count())->toBe(4);
});
