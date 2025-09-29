<?php

namespace DirectoryTree\Metrics\Tests;

use DirectoryTree\Metrics\MetricServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [MetricServiceProvider::class];
    }
}
