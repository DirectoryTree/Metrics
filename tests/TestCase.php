<?php

namespace DirectoryTree\Metrics\Tests;

use DirectoryTree\Metrics\MetricServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

use function Orchestra\Testbench\laravel_migration_path;

abstract class TestCase extends BaseTestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(laravel_migration_path('/'));
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [MetricServiceProvider::class];
    }
}
