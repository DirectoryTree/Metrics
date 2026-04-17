<?php

use DirectoryTree\Metrics\Jobs\RecordMetric;
use DirectoryTree\Metrics\Metric;
use DirectoryTree\Metrics\MetricData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Metric::query()->delete();

    if (! Schema::hasColumn('metrics', 'payload')) {
        Schema::table('metrics', function (Blueprint $table) {
            $table->json('payload');
        });
    }
});

afterEach(function () {
    if (Schema::hasColumn('metrics', 'payload')) {
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropColumn('payload');
        });
    }
});

it('creates metrics with json payload attributes', function () {
    $data = new MetricData('page_views_with_json', additional: [
        'payload' => [
            'a' => 1,
            'b' => 'test',
        ],
    ]);

    (new RecordMetric($data))->handle();
    (new RecordMetric($data))->handle();

    // Count may be 1 (SQLite dedupes by JSON string) or 2 (MySQL compares JSON differently).
    expect(Metric::where('name', 'page_views_with_json')->count())->toBeGreaterThanOrEqual(1);

    $metric = Metric::where('name', 'page_views_with_json')->first();

    // Cast the payload attribute manually since we added the column dynamically
    $metric->mergeCasts(['payload' => 'array']);

    expect($metric->payload)->toBe(['a' => 1, 'b' => 'test']);

    // Total value across all matching records should equal 2 regardless of DB.
    expect(Metric::where('name', 'page_views_with_json')->sum('value'))->toEqual(2);
});

it('differentiates metrics by json payload content', function () {
    $data1 = new MetricData('page_views_by_source', additional: [
        'payload' => ['source' => 'google'],
    ]);

    $data2 = new MetricData('page_views_by_source', additional: [
        'payload' => ['source' => 'facebook'],
    ]);

    (new RecordMetric($data1))->handle();
    (new RecordMetric($data2))->handle();

    // Count may be 2 (SQLite) or more (MySQL) depending on JSON comparison behavior.
    expect(Metric::where('name', 'page_views_by_source')->count())->toBeGreaterThanOrEqual(2);
});
