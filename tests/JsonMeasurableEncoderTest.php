<?php

use Carbon\CarbonImmutable;
use DirectoryTree\Metrics\JsonMeasurableEncoder;
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\Tests\User;
use Illuminate\Support\Facades\Date;

it('encodes a basic metric with only name', function () {
    $metric = new MetricData('page_views');

    $encoded = (new JsonMeasurableEncoder)->encode($metric);

    expect($encoded)->toBeString()
        ->and($encoded)->toContain('page_views');
});

it('encodes a metric with name and category', function () {
    Date::setTestNow('2025-10-12 12:00:00');

    $metric = new MetricData('page_views', 'marketing');

    $encoded = (new JsonMeasurableEncoder)->encode($metric);
    $decoded = json_decode($encoded, true);

    expect($decoded)->toBeArray()
        ->and($decoded['name'])->toBe('page_views')
        ->and($decoded['category'])->toBe('marketing')
        ->and($decoded['year'])->toBe(2025)
        ->and($decoded['month'])->toBe(10)
        ->and($decoded['day'])->toBe(12)
        ->and($decoded['measurable'])->toBeNull()
        ->and($decoded['measurable_key'])->toBeNull()
        ->and($decoded['measurable_id'])->toBeNull();
});

it('encodes a metric with custom date', function () {
    $metric = new MetricData(
        'page_views',
        date: CarbonImmutable::create(2025, 3, 15)
    );

    $encoded = (new JsonMeasurableEncoder)->encode($metric);
    $decoded = json_decode($encoded, true);

    expect($decoded['name'])->toBe('page_views')
        ->and($decoded['category'])->toBeNull()
        ->and($decoded['year'])->toBe(2025)
        ->and($decoded['month'])->toBe(3)
        ->and($decoded['day'])->toBe(15);
});

it('encodes a metric with measurable model', function () {
    Date::setTestNow('2025-10-12 12:00:00');

    $user = new User(['id' => 123]);

    $user->exists = true;

    $metric = new MetricData('logins', measurable: $user);

    $encoded = (new JsonMeasurableEncoder)->encode($metric);
    $decoded = json_decode($encoded, true);

    expect($decoded['name'])->toBe('logins')
        ->and($decoded['measurable'])->toBe('DirectoryTree\Metrics\Tests\User')
        ->and($decoded['measurable_key'])->toBe('id')
        ->and($decoded['measurable_id'])->toBe(123);
});

it('encodes a metric with all properties', function () {
    $metric = new MetricData(
        name: 'api_calls',
        category: 'external',
        value: 10,
        date: CarbonImmutable::create(2025, 6, 20),
        measurable: new User(['id' => 456])
    );

    $encoded = (new JsonMeasurableEncoder)->encode($metric);
    $decoded = json_decode($encoded, true);

    expect($decoded['name'])->toBe('api_calls')
        ->and($decoded['category'])->toBe('external')
        ->and($decoded['year'])->toBe(2025)
        ->and($decoded['month'])->toBe(6)
        ->and($decoded['day'])->toBe(20)
        ->and($decoded['measurable'])->toBe('DirectoryTree\Metrics\Tests\User')
        ->and($decoded['measurable_key'])->toBe('id')
        ->and($decoded['measurable_id'])->toBe(456);
});

it('encodes null category as empty string', function () {
    $metric = new MetricData('page_views', null);

    $encoded = (new JsonMeasurableEncoder)->encode($metric);
    $decoded = json_decode($encoded, true);

    expect($decoded['category'])->toBeNull();
});

it('encodes metric without measurable with null fields', function () {
    $metric = new MetricData('page_views');

    $encoded = (new JsonMeasurableEncoder)->encode($metric);
    $decoded = json_decode($encoded, true);

    expect($decoded['measurable'])->toBeNull()
        ->and($decoded['measurable_key'])->toBeNull()
        ->and($decoded['measurable_id'])->toBeNull();
});

it('encodes metrics with special characters in name', function () {
    $metric = new MetricData('page:views/home-page');

    $encoded = (new JsonMeasurableEncoder)->encode($metric);
    $decoded = json_decode($encoded, true);

    expect($decoded['name'])->toBe('page:views/home-page');
});

it('produces consistent encoding for same metric', function () {
    $date = CarbonImmutable::create(2025, 1, 15);

    $metric1 = new MetricData('page_views', 'marketing', date: $date);
    $metric2 = new MetricData('page_views', 'marketing', date: $date);

    expect((new JsonMeasurableEncoder)->encode($metric1))->toBe(
        (new JsonMeasurableEncoder)->encode($metric2)
    );
});

it('decodes a basic metric', function () {
    $key = json_encode([
        'name' => 'page_views',
        'category' => 'marketing',
        'year' => 2025,
        'month' => 1,
        'day' => 15,
        'measurable' => null,
        'measurable_key' => null,
        'measurable_id' => null,
    ]);

    $metric = (new JsonMeasurableEncoder)->decode($key, 1);

    expect($metric)->toBeInstanceOf(MetricData::class)
        ->and($metric->name())->toBe('page_views')
        ->and($metric->category())->toBe('marketing')
        ->and($metric->value())->toBe(1)
        ->and($metric->year())->toBe(2025)
        ->and($metric->month())->toBe(1)
        ->and($metric->day())->toBe(15)
        ->and($metric->measurable())->toBeNull();
});

it('decodes a metric with category', function () {
    $key = json_encode([
        'name' => 'page_views',
        'category' => 'marketing',
        'year' => 2025,
        'month' => 1,
        'day' => 15,
        'measurable' => null,
        'measurable_key' => null,
        'measurable_id' => null,
    ]);

    $metric = (new JsonMeasurableEncoder)->decode($key, 5);

    expect($metric->name())->toBe('page_views')
        ->and($metric->category())->toBe('marketing')
        ->and($metric->value())->toBe(5);
});

it('decodes a metric with custom value', function () {
    $key = json_encode([
        'name' => 'api_calls',
        'category' => '',
        'year' => 2025,
        'month' => 1,
        'day' => 15,
        'measurable' => null,
        'measurable_key' => null,
        'measurable_id' => null,
    ]);

    $metric = (new JsonMeasurableEncoder)->decode($key, 100);

    expect($metric->value())->toBe(100);
});

it('decodes a metric with custom date', function () {
    $key = json_encode([
        'name' => 'page_views',
        'category' => '',
        'year' => 2025,
        'month' => 6,
        'day' => 20,
        'measurable' => null,
        'measurable_key' => null,
        'measurable_id' => null,
    ]);

    $metric = (new JsonMeasurableEncoder)->decode($key, 1);

    expect($metric->year())->toBe(2025)
        ->and($metric->month())->toBe(6)
        ->and($metric->day())->toBe(20);
});

it('decodes a metric with measurable model', function () {
    $user = createUser();

    $key = json_encode([
        'name' => 'logins',
        'category' => '',
        'year' => 2025,
        'month' => 1,
        'day' => 15,
        'measurable' => User::class,
        'measurable_key' => 'id',
        'measurable_id' => $user->id,
    ]);

    $metric = (new JsonMeasurableEncoder)->decode($key, 1);

    $model = $metric->measurable();

    expect($model)->toBeInstanceOf(User::class)
        ->and($model->exists)->toBeTrue()
        ->and($model->getKey())->toBe($user->id)
        ->and($model->getKeyName())->toBe('id');
});

it('produces valid json output', function () {
    $metric = new MetricData('page_views', 'marketing');

    $encoded = (new JsonMeasurableEncoder)->encode($metric);

    expect(json_decode($encoded))->not->toBeNull()
        ->and(json_last_error())->toBe(JSON_ERROR_NONE);
});

it('handles special characters without escaping issues', function () {
    $metric = new MetricData('page:views/home-page', 'category|with|pipes');

    $encoded = (new JsonMeasurableEncoder)->encode($metric);
    $decoded = json_decode($encoded, true);

    expect($decoded['name'])->toBe('page:views/home-page')
        ->and($decoded['category'])->toBe('category|with|pipes');
});

it('round-trips encode and decode correctly', function () {
    Date::setTestNow('2025-10-12 12:00:00');

    $user = new User(['id' => 999]);
    $user->exists = true;

    $original = new MetricData('api_calls', 'external', 5, null, $user);

    $encoded = (new JsonMeasurableEncoder)->encode($original);
    $decoded = (new JsonMeasurableEncoder)->decode($encoded, 5);

    expect($decoded->name())->toBe($original->name())
        ->and($decoded->category())->toBe($original->category())
        ->and($decoded->value())->toBe($original->value())
        ->and($decoded->year())->toBe($original->year())
        ->and($decoded->month())->toBe($original->month())
        ->and($decoded->day())->toBe($original->day())
        ->and($decoded->measurable()?->getKey())->toBe($original->measurable()?->getKey());
});

it('ignores unknown fields when decoding', function () {
    $key = json_encode([
        'name' => 'page_views',
        'category' => 'marketing',
        'year' => 2025,
        'month' => 1,
        'day' => 15,
        'measurable' => null,
        'measurable_key' => null,
        'measurable_id' => null,
        'unknown_field' => 'should be ignored',
        'another_unknown' => 123,
    ]);

    $metric = (new JsonMeasurableEncoder)->decode($key, 1);

    expect($metric)->toBeInstanceOf(MetricData::class)
        ->and($metric->name())->toBe('page_views')
        ->and($metric->category())->toBe('marketing');
});
