<?php

use DirectoryTree\Metrics\Facades\Metrics;
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\MetricFake;
use DirectoryTree\Metrics\Tests\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\AssertionFailedError;

it('can be instantiated', function () {
    $fake = new MetricFake;

    expect($fake)->toBeInstanceOf(MetricFake::class);
});

it('records metrics', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));

    expect($fake->recorded()->count())->toBe(1);
});

it('records multiple metrics', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('api_calls'));
    $fake->record(new MetricData('errors'));

    expect($fake->recorded()->count())->toBe(3);
});

it('can assert metric was recorded by name', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));

    $fake->assertRecorded('page_views');
});

it('can assert metric was recorded with closure', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views', 'marketing'));

    $fake->assertRecorded(fn ($metric) => $metric->name() === 'page_views' && $metric->category() === 'marketing');
});

it('can assert metric was not recorded', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));

    $fake->assertNotRecorded('api_calls');
});

it('can assert metric was not recorded with closure', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views', 'marketing'));

    $fake->assertNotRecorded(fn ($metric) => $metric->category() === 'analytics');
});

it('can assert nothing was recorded', function () {
    $fake = new MetricFake;

    $fake->assertNothingRecorded();
});

it('can assert metric was recorded specific times', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('page_views'));

    $fake->assertRecordedTimes('page_views', 3);
});

it('can assert metric was recorded specific times with closure', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views', 'marketing'));
    $fake->record(new MetricData('page_views', 'marketing'));
    $fake->record(new MetricData('page_views', 'analytics'));

    $fake->assertRecordedTimes(fn ($metric) => $metric->category() === 'marketing', 2);
});

it('can get recorded metrics by name', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('api_calls'));

    $recorded = $fake->recorded('page_views');

    expect($recorded->count())->toBe(1)
        ->and($recorded->first()->name())->toBe('page_views');
});

it('can get recorded metrics with closure', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views', 'marketing'));
    $fake->record(new MetricData('page_views', 'analytics'));

    $recorded = $fake->recorded(fn ($metric) => $metric->category() === 'marketing');

    expect($recorded->count())->toBe(1)
        ->and($recorded->first()->category())->toBe('marketing');
});

it('can get all recorded metrics', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('api_calls'));

    $recorded = $fake->recorded();

    expect($recorded->count())->toBe(2);
});

it('commit clears recorded metrics', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('api_calls'));

    expect($fake->recorded()->count())->toBe(2);

    $fake->commit();

    expect($fake->recorded()->count())->toBe(0);
});

it('can start capturing', function () {
    $fake = new MetricFake;

    expect($fake->isCapturing())->toBeFalse();

    $fake->capture();

    expect($fake->isCapturing())->toBeTrue();
});

it('can stop capturing', function () {
    $fake = new MetricFake;

    $fake->capture();
    expect($fake->isCapturing())->toBeTrue();

    $fake->stopCapturing();

    expect($fake->isCapturing())->toBeFalse();
});

it('records metrics regardless of capturing state', function () {
    $fake = new MetricFake;

    $fake->capture();
    $fake->record(new MetricData('page_views'));

    expect($fake->recorded()->count())->toBe(1);
});

it('can be used with facade', function () {
    Metrics::fake();

    Metrics::record(new MetricData('page_views'));

    Metrics::assertRecorded('page_views');
});

it('facade fake returns MetricFake instance', function () {
    $fake = Metrics::fake();

    expect($fake)->toBeInstanceOf(MetricFake::class);
});

it('can assert metrics with different properties', function () {
    $fake = new MetricFake;

    $user = User::create([
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $fake->record(new MetricData('logins', measurable: $user));

    $fake->assertRecorded(fn ($metric) => $metric->name() === 'logins' &&
        $metric->measurable()?->is($user)
    );
});

it('can filter recorded metrics by multiple criteria', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views', 'marketing', value: 5));
    $fake->record(new MetricData('page_views', 'analytics', value: 3));
    $fake->record(new MetricData('api_calls', 'marketing', value: 2));

    $recorded = $fake->recorded(fn ($metric) => $metric->name() === 'page_views' &&
        $metric->category() === 'marketing'
    );

    expect($recorded->count())->toBe(1)
        ->and($recorded->first()->value())->toBe(5);
});

it('returns empty collection when no metrics match', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));

    $recorded = $fake->recorded('api_calls');

    expect($recorded->isEmpty())->toBeTrue();
});

it('can record same metric multiple times', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('page_views'));

    expect($fake->recorded('page_views')->count())->toBe(3);
});

it('maintains order of recorded metrics', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('first'));
    $fake->record(new MetricData('second'));
    $fake->record(new MetricData('third'));

    $recorded = $fake->recorded();

    expect($recorded->get(0)->name())->toBe('first')
        ->and($recorded->get(1)->name())->toBe('second')
        ->and($recorded->get(2)->name())->toBe('third');
});

it('can record and commit multiple times', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));
    $fake->commit();

    expect($fake->recorded()->count())->toBe(0);

    $fake->record(new MetricData('api_calls'));

    expect($fake->recorded()->count())->toBe(1);
});

it('assertion fails when metric not recorded', function () {
    $fake = new MetricFake;

    expect(fn () => $fake->assertRecorded('page_views'))
        ->toThrow(AssertionFailedError::class);
});

it('assertion fails when metric was recorded but should not be', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));

    expect(fn () => $fake->assertNotRecorded('page_views'))
        ->toThrow(AssertionFailedError::class);
});

it('assertion fails when nothing recorded but something was', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));

    expect(fn () => $fake->assertNothingRecorded())
        ->toThrow(AssertionFailedError::class);
});

it('assertion fails when recorded times does not match', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));
    $fake->record(new MetricData('page_views'));

    expect(fn () => $fake->assertRecordedTimes('page_views', 3))
        ->toThrow(AssertionFailedError::class);
});

it('can assert with complex closure conditions', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views', 'marketing', value: 100));

    $fake->assertRecorded(fn ($metric) => $metric->name() === 'page_views' &&
        $metric->category() === 'marketing' &&
        $metric->value() >= 100
    );
});

it('recorded returns collection instance', function () {
    $fake = new MetricFake;

    $fake->record(new MetricData('page_views'));

    expect($fake->recorded())->toBeInstanceOf(Collection::class);
});
