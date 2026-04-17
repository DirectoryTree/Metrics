<?php

use DirectoryTree\Metrics\Jobs\RecordMetric;
use DirectoryTree\Metrics\Metric;
use DirectoryTree\Metrics\MetricData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
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

    $metric = Metric::where('name', 'page_views_with_json')->first();

    // Cast the payload attribute manually since we added the column dynamically
    $metric->mergeCasts(['payload' => 'array']);

    expect($metric->payload)->toBe(['a' => 1, 'b' => 'test']);
    expect($metric->value)->toBe(2);
});

it('differentiates metrics by json payload content', function () {
    $data1 = new MetricData('page_views_by_source', additional: [
        'payload' => ['source' => 'google'],
    ]);

    $data2 = new MetricData('page_views_by_source', additional: [
        'payload' => ['source' => 'facebook'],
    ]);

    (new RecordMetric($data1))->handle();
    (new RecordMetric($data1))->handle();
    (new RecordMetric($data2))->handle();

    $metricsCount = Metric::where('name', 'page_views_by_source')->count();
    expect($metricsCount)->toBe(2);

    $google = Metric::where('name', 'page_views_by_source')
        ->where('payload->source', 'google')
        ->first();
    $facebook = Metric::where('name', 'page_views_by_source')
        ->where('payload->source', 'facebook')
        ->first();

    // Cast the payload attribute manually
    $google->mergeCasts(['payload' => 'array']);
    $facebook->mergeCasts(['payload' => 'array']);

    expect($google->value)->toBe(2);
    expect($facebook->value)->toBe(1);
});

it('handles nested json structures', function () {
    $data = new MetricData('api_requests', additional: [
        'payload' => [
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
            ],
            'metadata' => [
                'ip' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
            ],
        ],
    ]);

    (new RecordMetric($data))->handle();

    $metric = Metric::where('name', 'api_requests')->first();
    $metric->mergeCasts(['payload' => 'array']);

    expect($metric->payload['user']['id'])->toBe(123);
    expect($metric->payload['metadata']['ip'])->toBe('192.168.1.1');
});

it('can query metrics by nested json attributes', function () {
    $data1 = new MetricData('events', additional: [
        'payload' => [
            'type' => 'click',
            'element' => ['id' => 'button-1', 'class' => 'primary'],
        ],
    ]);

    $data2 = new MetricData('events', additional: [
        'payload' => [
            'type' => 'click',
            'element' => ['id' => 'button-2', 'class' => 'secondary'],
        ],
    ]);

    (new RecordMetric($data1))->handle();
    (new RecordMetric($data2))->handle();

    $button1Clicks = Metric::where('name', 'events')
        ->where('payload->element->id', 'button-1')
        ->first();

    $button1Clicks->mergeCasts(['payload' => 'array']);

    expect($button1Clicks->value)->toBe(1);
    expect($button1Clicks->payload['element']['class'])->toBe('primary');
});

it('combines scalar and json additional attributes', function () {
    Schema::table('metrics', function (Blueprint $table) {
        $table->string('source')->nullable();
    });

    $data = new MetricData('mixed_attributes', additional: [
        'source' => 'google',
        'payload' => [
            'campaign' => 'summer-sale',
            'ad_id' => 12345,
        ],
    ]);

    (new RecordMetric($data))->handle();

    $metric = Metric::where('name', 'mixed_attributes')->first();
    $metric->mergeCasts(['payload' => 'array']);

    expect($metric->source)->toBe('google');
    expect($metric->payload['campaign'])->toBe('summer-sale');
    expect($metric->payload['ad_id'])->toBe(12345);

    Schema::table('metrics', function (Blueprint $table) {
        $table->dropColumn('source');
    });
});
