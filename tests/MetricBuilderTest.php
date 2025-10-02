<?php

use DirectoryTree\Metrics\Metric;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2025-10-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('can filter metrics for today', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 100]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 14, 'value' => 50]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 16, 'value' => 75]);

    $metrics = Metric::today()->get();

    expect($metrics)->toHaveCount(1)
        ->and($metrics->first()->day)->toBe(15)
        ->and($metrics->first()->value)->toBe(100);
});

it('can filter metrics for yesterday', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 14, 'value' => 100]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 50]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 13, 'value' => 75]);

    $metrics = Metric::yesterday()->get();

    expect($metrics)->toHaveCount(1)
        ->and($metrics->first()->day)->toBe(14)
        ->and($metrics->first()->value)->toBe(100);
});

it('can filter metrics for this week', function () {
    // Week starts Monday Oct 13, ends Sunday Oct 19
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 13, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 19, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 12, 'value' => 40]); // Last week
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 20, 'value' => 50]); // Next week

    $metrics = Metric::thisWeek()->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics for last week', function () {
    // Last week: Monday Oct 6 to Sunday Oct 12
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 6, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 10, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 12, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 5, 'value' => 40]); // Before last week
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 13, 'value' => 50]); // This week

    $metrics = Metric::lastWeek()->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics for this month', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 1, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 31, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 9, 'day' => 30, 'value' => 40]); // Last month
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 11, 'day' => 1, 'value' => 50]); // Next month

    $metrics = Metric::thisMonth()->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics for last month', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 9, 'day' => 1, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 9, 'day' => 15, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 9, 'day' => 30, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 8, 'day' => 31, 'value' => 40]); // August
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 1, 'value' => 50]); // This month

    $metrics = Metric::lastMonth()->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics for this quarter', function () {
    // Q4: Oct, Nov, Dec
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 1, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 11, 'day' => 15, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 12, 'day' => 31, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 9, 'day' => 30, 'value' => 40]); // Q3
    Metric::create(['name' => 'page_views', 'year' => 2026, 'month' => 1, 'day' => 1, 'value' => 50]); // Q1 next year

    $metrics = Metric::thisQuarter()->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics for last quarter', function () {
    // Q3: Jul, Aug, Sep
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 7, 'day' => 1, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 8, 'day' => 15, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 9, 'day' => 30, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 6, 'day' => 30, 'value' => 40]); // Q2
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 1, 'value' => 50]); // Q4

    $metrics = Metric::lastQuarter()->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics for this year', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 1, 'day' => 1, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 6, 'day' => 15, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 12, 'day' => 31, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2024, 'month' => 12, 'day' => 31, 'value' => 40]); // Last year
    Metric::create(['name' => 'page_views', 'year' => 2026, 'month' => 1, 'day' => 1, 'value' => 50]); // Next year

    $metrics = Metric::thisYear()->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics for last year', function () {
    Metric::create(['name' => 'page_views', 'year' => 2024, 'month' => 1, 'day' => 1, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2024, 'month' => 6, 'day' => 15, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2024, 'month' => 12, 'day' => 31, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2023, 'month' => 12, 'day' => 31, 'value' => 40]); // 2023
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 1, 'day' => 1, 'value' => 50]); // This year

    $metrics = Metric::lastYear()->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics between specific dates', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 10, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 20, 'value' => 30]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 9, 'value' => 40]); // Before range
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 21, 'value' => 50]); // After range

    $start = Carbon::create(2025, 10, 10);
    $end = Carbon::create(2025, 10, 20);

    $metrics = Metric::betweenDates($start, $end)->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter metrics on a specific date', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 100]);
    Metric::create(['name' => 'api_calls', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 200]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 14, 'value' => 50]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 16, 'value' => 75]);

    $date = Carbon::create(2025, 10, 15);

    $metrics = Metric::onDate($date)->get();

    expect($metrics)->toHaveCount(2)
        ->and($metrics->sum('value'))->toBe(300);
});

it('can chain builder methods with where clauses', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 100]);
    Metric::create(['name' => 'api_calls', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 200]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 14, 'value' => 50]);

    $metrics = Metric::today()->where('name', 'page_views')->get();

    expect($metrics)->toHaveCount(1)
        ->and($metrics->first()->name)->toBe('page_views')
        ->and($metrics->first()->value)->toBe(100);
});

it('can sum values for a date range', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 13, 'value' => 100]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 14, 'value' => 150]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 200]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 20, 'value' => 300]);

    $total = Metric::thisWeek()->where('name', 'page_views')->sum('value');

    expect($total)->toBe(450);
});

it('can count metrics for a date range', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 100]);
    Metric::create(['name' => 'api_calls', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 200]);
    Metric::create(['name' => 'errors', 'year' => 2025, 'month' => 10, 'day' => 15, 'value' => 50]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 14, 'value' => 75]);

    $count = Metric::today()->count();

    expect($count)->toBe(3);
});

it('returns empty collection when no metrics match date filter', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 14, 'value' => 100]);

    $metrics = Metric::today()->get();

    expect($metrics)->toBeEmpty();
});

it('can filter across month boundaries', function () {
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 9, 'day' => 30, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 1, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 10, 'day' => 2, 'value' => 30]);

    $start = Carbon::create(2025, 9, 30);
    $end = Carbon::create(2025, 10, 2);

    $metrics = Metric::betweenDates($start, $end)->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});

it('can filter across year boundaries', function () {
    Metric::create(['name' => 'page_views', 'year' => 2024, 'month' => 12, 'day' => 31, 'value' => 10]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 1, 'day' => 1, 'value' => 20]);
    Metric::create(['name' => 'page_views', 'year' => 2025, 'month' => 1, 'day' => 2, 'value' => 30]);

    $start = Carbon::create(2024, 12, 31);
    $end = Carbon::create(2025, 1, 2);

    $metrics = Metric::betweenDates($start, $end)->get();

    expect($metrics)->toHaveCount(3)
        ->and($metrics->sum('value'))->toBe(60);
});
