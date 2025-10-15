<?php

use DirectoryTree\Metrics\ArrayMetricRepository;
use DirectoryTree\Metrics\MetricData;

it('starts with an empty array', function () {
    $repository = app(ArrayMetricRepository::class);

    expect($repository->all())->toBeArray()
        ->and($repository->all())->toBeEmpty();
});

it('can add a metric', function () {
    $repository = app(ArrayMetricRepository::class);

    $metric = new MetricData('page_views');

    $repository->add($metric);

    $all = $repository->all();

    expect($all)->toHaveCount(1)
        ->and($all[0]->name())->toBe($metric->name())
        ->and($all[0]->value())->toBe($metric->value());
});

it('can add multiple metrics', function () {
    $repository = app(ArrayMetricRepository::class);

    $metric1 = new MetricData('page_views');
    $metric2 = new MetricData('api_calls');
    $metric3 = new MetricData('user_signups');

    $repository->add($metric1);
    $repository->add($metric2);
    $repository->add($metric3);

    $all = $repository->all();

    expect($all)->toHaveCount(3)
        ->and($all[0])->toBe($metric1)
        ->and($all[1])->toBe($metric2)
        ->and($all[2])->toBe($metric3);
});

it('can add the same metric multiple times', function () {
    $repository = app(ArrayMetricRepository::class);

    $metric = new MetricData('page_views');

    $repository->add($metric);
    $repository->add($metric);
    $repository->add($metric);

    $all = $repository->all();

    expect($all)->toHaveCount(1)
        ->and($all[0]->value())->toBe(3);
});

it('can flush all metrics', function () {
    $repository = app(ArrayMetricRepository::class);

    $repository->add(new MetricData('page_views'));
    $repository->add(new MetricData('api_calls'));

    expect($repository->all())->toHaveCount(2);

    $repository->flush();

    expect($repository->all())->toBeEmpty();
});

it('can add metrics after flushing', function () {
    $repository = app(ArrayMetricRepository::class);

    $repository->add(new MetricData('page_views'));

    $repository->flush();

    expect($repository->all())->toBeEmpty();

    $metric = new MetricData('api_calls');

    $repository->add($metric);

    $all = $repository->all();

    expect($all)->toHaveCount(1)
        ->and($all[0])->toBe($metric);
});

it('maintains order of added metrics', function () {
    $repository = app(ArrayMetricRepository::class);

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
