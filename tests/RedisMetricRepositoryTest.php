<?php

use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\RedisMetricRepository;
use DirectoryTree\Metrics\Tests\User;
use Illuminate\Support\Facades\Redis;

beforeEach(fn () => Redis::flushdb());
afterEach(fn () => Redis::flushdb());

it('starts with an empty list', function () {
    $repository = app(RedisMetricRepository::class);

    expect($repository->all())->toBeArray()
        ->and($repository->all())->toBeEmpty();
});

it('can add a metric', function () {
    $repository = app(RedisMetricRepository::class);

    $metric = new MetricData('page_views');

    $repository->add($metric);

    expect($repository->all())->toHaveCount(1)
        ->and($repository->all()[0]->name())->toBe('page_views')
        ->and($repository->all()[0]->value())->toBe(1);
});

it('can add multiple metrics', function () {
    $repository = app(RedisMetricRepository::class);

    $metric1 = new MetricData('page_views');
    $metric2 = new MetricData('api_calls');
    $metric3 = new MetricData('user_signups');

    $repository->add($metric1);
    $repository->add($metric2);
    $repository->add($metric3);

    expect($repository->all())->toHaveCount(3)
        ->and($repository->all()[0]->name())->toBe('page_views')
        ->and($repository->all()[1]->name())->toBe('api_calls')
        ->and($repository->all()[2]->name())->toBe('user_signups');
});

it('can add the same metric multiple times', function () {
    $repository = app(RedisMetricRepository::class);

    $metric = new MetricData('page_views');

    $repository->add($metric);
    $repository->add($metric);
    $repository->add($metric);

    expect($repository->all())->toHaveCount(1)
        ->and($repository->all()[0]->name())->toBe('page_views')
        ->and($repository->all()[0]->value())->toBe(3);
});

it('can flush all metrics', function () {
    $repository = app(RedisMetricRepository::class);

    $repository->add(new MetricData('page_views'));
    $repository->add(new MetricData('api_calls'));

    expect($repository->all())->toHaveCount(2);

    $repository->flush();

    expect($repository->all())->toBeEmpty();
});

it('can add metrics after flushing', function () {
    $repository = app(RedisMetricRepository::class);

    $repository->add(new MetricData('page_views'));

    $repository->flush();

    expect($repository->all())->toBeEmpty();

    $metric = new MetricData('api_calls');

    $repository->add($metric);

    expect($repository->all())->toHaveCount(1)
        ->and($repository->all()[0]->name())->toBe('api_calls');
});

it('maintains order of added metrics', function () {
    $repository = app(RedisMetricRepository::class);

    $metrics = [
        new MetricData('first'),
        new MetricData('second'),
        new MetricData('third'),
        new MetricData('fourth'),
    ];

    foreach ($metrics as $metric) {
        $repository->add($metric);
    }

    $all = $repository->all();

    expect($all[0]->name())->toBe('first')
        ->and($all[1]->name())->toBe('second')
        ->and($all[2]->name())->toBe('third')
        ->and($all[3]->name())->toBe('fourth');
});

it('correctly serializes and unserializes metrics with categories', function () {
    $repository = app(RedisMetricRepository::class);

    $metric = new MetricData('page_views', 'marketing', 5);

    $repository->add($metric);

    $retrieved = $repository->all()[0];

    expect($retrieved->name())->toBe('page_views')
        ->and($retrieved->category())->toBe('marketing')
        ->and($retrieved->value())->toBe(5);
});

it('correctly serializes and unserializes metrics with dates', function () {
    $repository = app(RedisMetricRepository::class);

    $date = now()->subDays(3);
    $metric = new MetricData('page_views', null, 1, $date);

    $repository->add($metric);

    $retrieved = $repository->all()[0];

    expect($retrieved->year())->toBe($date->year)
        ->and($retrieved->month())->toBe($date->month)
        ->and($retrieved->day())->toBe($date->day);
});

it('correctly serializes and unserializes metrics with measurable models', function () {
    $repository = app(RedisMetricRepository::class);

    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $metric = new MetricData('page_views', null, 1, null, $user);

    $repository->add($metric);

    $retrieved = $repository->all()[0];

    expect($retrieved->measurable())->not->toBeNull()
        ->and($retrieved->measurable()->getKey())->toBe($user->getKey())
        ->and($retrieved->measurable()->getMorphClass())->toBe($user->getMorphClass());
});

it('uses custom redis key from config', function () {
    config(['metrics.redis.key' => 'custom:metrics:key']);

    $repository = app(RedisMetricRepository::class);

    $repository->add(new MetricData('page_views'));

    expect(Redis::exists('custom:metrics:key'))->toBe(1);
});

it('uses default redis key when not configured', function () {
    config(['metrics.redis.key' => null]);

    $repository = app(RedisMetricRepository::class);

    $repository->add(new MetricData('page_views'));

    // Verify the default key exists in Redis
    expect(Redis::exists('metrics:pending'))->toBe(1);
});

it('persists metrics across repository instances', function () {
    $repository1 = app(RedisMetricRepository::class);

    $repository1->add(new MetricData('page_views'));
    $repository1->add(new MetricData('api_calls'));

    // Create a new instance
    $repository2 = app(RedisMetricRepository::class);

    expect($repository2->all())->toHaveCount(2)
        ->and($repository2->all()[0]->name())->toBe('page_views')
        ->and($repository2->all()[1]->name())->toBe('api_calls');
});

it('handles large numbers of metrics', function () {
    $repository = app(RedisMetricRepository::class);

    for ($i = 0; $i < 100; $i++) {
        $repository->add(new MetricData("metric_{$i}"));
    }

    expect($repository->all())->toHaveCount(100);
});

it('handles metrics with special characters in names', function () {
    $repository = app(RedisMetricRepository::class);

    $metric = new MetricData('page:views/home-page');

    $repository->add($metric);

    $retrieved = $repository->all()[0];

    expect($retrieved->name())->toBe('page:views/home-page');
});

it('handles empty flush gracefully', function () {
    $repository = app(RedisMetricRepository::class);

    $repository->flush();

    expect($repository->all())->toBeEmpty();
});
