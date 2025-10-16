<?php

use Carbon\CarbonImmutable;
use DirectoryTree\Metrics\Jobs\RecordMetric;
use DirectoryTree\Metrics\Metric;
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\Tests\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('can record a single metric', function () {
    $metric = new MetricData('page_views');

    (new RecordMetric($metric))->handle();

    expect(Metric::count())->toBe(1);

    $recorded = Metric::first();

    expect($recorded->name)->toBe('page_views')
        ->and($recorded->value)->toBe(1);
});

it('can record a collection of metrics', function () {
    $metrics = collect([
        new MetricData('page_views', value: 5),
        new MetricData('page_views', value: 3),
        new MetricData('page_views', value: 2),
    ]);

    (new RecordMetric($metrics))->handle();

    expect(Metric::count())->toBe(1);

    $recorded = Metric::first();

    expect($recorded->name)->toBe('page_views')
        ->and($recorded->value)->toBe(10); // 5 + 3 + 2
});

it('creates a new metric if it does not exist', function () {
    expect(Metric::count())->toBe(0);

    (new RecordMetric(new MetricData('api_calls')))->handle();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->name)->toBe('api_calls')
        ->and(Metric::first()->value)->toBe(1);
});

it('increments existing metric value', function () {
    Metric::create([
        'name' => 'page_views',
        'category' => null,
        'year' => now()->year,
        'month' => now()->month,
        'day' => now()->day,
        'measurable_type' => null,
        'measurable_id' => null,
        'value' => 10,
    ]);

    (new RecordMetric(new MetricData('page_views', value: 5)))->handle();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->value)->toBe(15); // 10 + 5
});

it('handles empty collection gracefully', function () {
    (new RecordMetric(collect()))->handle();

    expect(Metric::count())->toBe(0);
});

it('records metric with category', function () {
    (new RecordMetric(new MetricData('page_views', 'marketing')))->handle();

    $recorded = Metric::first();

    expect($recorded->name)->toBe('page_views')
        ->and($recorded->category)->toBe('marketing')
        ->and($recorded->value)->toBe(1);
});

it('records metric with specific date', function () {
    $date = CarbonImmutable::parse('2025-03-15');

    (new RecordMetric(new MetricData('page_views', date: $date)))->handle();

    $recorded = Metric::first();

    expect($recorded->year)->toBe(2025)
        ->and($recorded->month)->toBe(3)
        ->and($recorded->day)->toBe(15);
});

it('records metric with measurable model', function () {
    $user = createUser();

    (new RecordMetric(new MetricData('logins', measurable: $user)))->handle();

    $recorded = Metric::first();

    expect($recorded->name)->toBe('logins')
        ->and($recorded->measurable_type)->toBe(User::class)
        ->and($recorded->measurable_id)->toBe($user->id)
        ->and($recorded->value)->toBe(1);
});

it('treats metrics with different categories as separate', function () {
    (new RecordMetric(new MetricData('page_views', 'marketing')))->handle();
    (new RecordMetric(new MetricData('page_views', 'analytics')))->handle();

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('category', 'marketing')->first()->value)->toBe(1)
        ->and(Metric::where('category', 'analytics')->first()->value)->toBe(1);
});

it('treats metrics with different dates as separate', function () {
    $today = CarbonImmutable::parse('2025-01-15');
    $yesterday = CarbonImmutable::parse('2025-01-14');

    (new RecordMetric(new MetricData('page_views', date: $today)))->handle();
    (new RecordMetric(new MetricData('page_views', date: $yesterday)))->handle();

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('day', 15)->first()->value)->toBe(1)
        ->and(Metric::where('day', 14)->first()->value)->toBe(1);
});

it('treats metrics with different measurable models as separate', function () {
    $user1 = createUser(['name' => 'John', 'email' => 'john@example.com']);
    $user2 = createUser(['name' => 'Jane', 'email' => 'jane@example.com']);

    (new RecordMetric(new MetricData('logins', measurable: $user1)))->handle();
    (new RecordMetric(new MetricData('logins', measurable: $user2)))->handle();

    expect(Metric::count())->toBe(2)
        ->and(Metric::where('measurable_id', $user1->id)->first()->value)->toBe(1)
        ->and(Metric::where('measurable_id', $user2->id)->first()->value)->toBe(1);
});

it('sums values from collection of same metrics', function () {
    $metrics = collect([
        new MetricData('page_views', value: 1),
        new MetricData('page_views', value: 1),
        new MetricData('page_views', value: 1),
    ]);

    (new RecordMetric($metrics))->handle();

    expect(Metric::first()->value)->toBe(3);
});

it('handles metrics with null category', function () {
    (new RecordMetric(new MetricData('page_views', null)))->handle();

    $recorded = Metric::first();

    expect($recorded->category)->toBeNull()
        ->and($recorded->value)->toBe(1);
});

it('handles metrics with null measurable', function () {
    (new RecordMetric(new MetricData('page_views')))->handle();

    $recorded = Metric::first();

    expect($recorded->measurable_type)->toBeNull()
        ->and($recorded->measurable_id)->toBeNull()
        ->and($recorded->value)->toBe(1);
});

it('records multiple different metrics separately', function () {
    (new RecordMetric(new MetricData('page_views')))->handle();
    (new RecordMetric(new MetricData('api_calls')))->handle();
    (new RecordMetric(new MetricData('errors')))->handle();

    expect(Metric::count())->toBe(3)
        ->and(Metric::where('name', 'page_views')->exists())->toBeTrue()
        ->and(Metric::where('name', 'api_calls')->exists())->toBeTrue()
        ->and(Metric::where('name', 'errors')->exists())->toBeTrue();
});

it('increments value multiple times for same metric', function () {
    (new RecordMetric(new MetricData('page_views', value: 1)))->handle();
    (new RecordMetric(new MetricData('page_views', value: 2)))->handle();
    (new RecordMetric(new MetricData('page_views', value: 3)))->handle();

    expect(Metric::count())->toBe(1)
        ->and(Metric::first()->value)->toBe(6); // 1 + 2 + 3
});

it('handles large value increments', function () {
    (new RecordMetric(new MetricData('page_views', value: 1000)))->handle();
    (new RecordMetric(new MetricData('page_views', value: 5000)))->handle();

    expect(Metric::first()->value)->toBe(6000);
});

it('sets timestamps on creation', function () {
    (new RecordMetric(new MetricData('page_views')))->handle();

    $recorded = Metric::first();

    expect($recorded->created_at)->not->toBeNull()
        ->and($recorded->updated_at)->not->toBeNull();
});

it('updates timestamp on increment', function () {
    Metric::create([
        'name' => 'page_views',
        'category' => null,
        'year' => now()->year,
        'month' => now()->month,
        'day' => now()->day,
        'measurable_type' => null,
        'measurable_id' => null,
        'value' => 1,
        'updated_at' => $originalUpdatedAt = now()->subDay(),
    ]);

    (new RecordMetric(new MetricData('page_views')))->handle();

    $updated = Metric::first();

    expect($updated->updated_at->isAfter($originalUpdatedAt))->toBeTrue();
});

it('creates metrics with additional attributes', function () {
    Schema::table('metrics', function (Blueprint $table) {
        $table->string('source')->nullable();
        $table->string('country')->nullable();
    });

    $data = new MetricData('page_views', additional: [
        'source' => 'google',
        'country' => 'US',
    ]);

    (new RecordMetric($data))->handle();
    (new RecordMetric($data))->handle();

    $metric = Metric::first();

    expect($metric->source)->toBe('google');
    expect($metric->country)->toBe('US');
    expect($metric->value)->toBe(2);
});

it('cannot override core attributes with additional attributes', function () {
    $data = new MetricData('page_views', additional: [
        'name' => 'api_calls',
        'category' => 'marketing',
        'year' => 2025,
        'month' => 1,
        'day' => 1,
        'measurable_type' => 'App\Models\User',
        'measurable_id' => 1,
        'value' => 100,
    ]);

    (new RecordMetric($data))->handle();

    $recorded = Metric::first();

    expect($recorded->name)->toBe('page_views')
        ->and($recorded->category)->toBeNull()
        ->and($recorded->year)->toBe(today()->year)
        ->and($recorded->month)->toBe(today()->month)
        ->and($recorded->day)->toBe(today()->day)
        ->and($recorded->measurable_type)->toBeNull()
        ->and($recorded->measurable_id)->toBeNull()
        ->and($recorded->value)->toBe(1);
});
