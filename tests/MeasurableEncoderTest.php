<?php

use Carbon\CarbonImmutable;
use DirectoryTree\Metrics\MeasurableEncoder;
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\Tests\User;
use Illuminate\Support\Facades\Date;

it('encodes a basic metric with only name', function () {
    $metric = new MetricData('page_views');

    $encoded = (new MeasurableEncoder)->encode($metric);

    expect($encoded)->toBeString()
        ->and($encoded)->toContain('page_views')
        ->and($encoded)->toContain('|');
});

it('encodes a metric with name and category', function () {
    Date::setTestNow('2025-10-12 12:00:00');

    $metric = new MetricData('page_views', 'marketing');

    $encoded = (new MeasurableEncoder)->encode($metric);

    expect($encoded)->toBe('page_views|marketing|2025|10|12|||');
});

it('encodes a metric with custom date', function () {
    $metric = new MetricData(
        'page_views',
        date: CarbonImmutable::create(2025, 3, 15)
    );

    $encoded = (new MeasurableEncoder)->encode($metric);

    expect($encoded)->toBe('page_views||2025|3|15|||');
});

it('encodes a metric with measurable model', function () {
    Date::setTestNow('2025-10-12 12:00:00');

    $user = new User(['id' => 123]);

    $user->exists = true;

    $metric = new MetricData('logins', measurable: $user);

    $encoded = (new MeasurableEncoder)->encode($metric);

    expect($encoded)->toBe('logins||2025|10|12|DirectoryTree\Metrics\Tests\User|id|123');
});

it('encodes a metric with all properties', function () {
    $metric = new MetricData(
        name: 'api_calls',
        category: 'external',
        value: 10,
        date: CarbonImmutable::create(2025, 6, 20),
        measurable: new User(['id' => 456])
    );

    $encoded = (new MeasurableEncoder)->encode($metric);

    expect($encoded)->toBe('api_calls|external|2025|6|20|DirectoryTree\Metrics\Tests\User|id|456');
});

it('encodes null category as empty string', function () {
    $metric = new MetricData('page_views', null);

    $encoded = (new MeasurableEncoder)->encode($metric);

    $parts = explode('|', $encoded);

    expect($parts[1])->toBe('');
});

it('encodes metric without measurable with empty fields', function () {
    $metric = new MetricData('page_views');

    $encoded = (new MeasurableEncoder)->encode($metric);

    $parts = explode('|', $encoded);

    expect($parts)->toHaveCount(8)
        ->and($parts[5])->toBe('') // measurable class
        ->and($parts[6])->toBe('') // measurable key name
        ->and($parts[7])->toBe(''); // measurable id
});

it('encodes metrics with special characters in name', function () {
    $metric = new MetricData('page:views/home-page');

    $encoded = (new MeasurableEncoder)->encode($metric);

    expect($encoded)->toContain('page:views/home-page');
});

it('produces consistent encoding for same metric', function () {
    $date = CarbonImmutable::create(2025, 1, 15);

    $metric1 = new MetricData('page_views', 'marketing', date: $date);
    $metric2 = new MetricData('page_views', 'marketing', date: $date);

    expect((new MeasurableEncoder)->encode($metric1))->toBe(
        (new MeasurableEncoder)->encode($metric2)
    );
});

it('decodes a basic metric', function () {
    $key = 'page_views||2025|1|15|||';

    $metric = (new MeasurableEncoder)->decode($key, 1);

    expect($metric)->toBeInstanceOf(MetricData::class)
        ->and($metric->name())->toBe('page_views')
        ->and($metric->category())->toBeNull()
        ->and($metric->value())->toBe(1)
        ->and($metric->year())->toBe(2025)
        ->and($metric->month())->toBe(1)
        ->and($metric->day())->toBe(15)
        ->and($metric->measurable())->toBeNull();
});

it('decodes a metric with category', function () {
    $key = 'page_views|marketing|2025|1|15|||';

    $metric = (new MeasurableEncoder)->decode($key, 5);

    expect($metric->name())->toBe('page_views')
        ->and($metric->category())->toBe('marketing')
        ->and($metric->value())->toBe(5);
});

it('decodes a metric with custom value', function () {
    $key = 'api_calls||2025|1|15|||';

    $metric = (new MeasurableEncoder)->decode($key, 100);

    expect($metric->value())->toBe(100);
});

it('decodes a metric with custom date', function () {
    $key = 'page_views||2025|6|20|||';

    $metric = (new MeasurableEncoder)->decode($key, 1);

    expect($metric->year())->toBe(2025)
        ->and($metric->month())->toBe(6)
        ->and($metric->day())->toBe(20);
});

it('decodes a metric with measurable model', function () {
    User::create(['id' => 123, 'name' => 'John', 'email' => 'john@example.com', 'password' => 'password']);

    $key = 'logins||2025|1|15|DirectoryTree\Metrics\Tests\User|id|123';

    $metric = (new MeasurableEncoder)->decode($key, 1);

    $model = $metric->measurable();

    expect($model)->toBeInstanceOf(User::class)
        ->and($model->exists)->toBeTrue()
        ->and($model->getKey())->toBe(123)
        ->and($model->getKeyName())->toBe('id');
});
