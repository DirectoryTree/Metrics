<?php

namespace DirectoryTree\Metrics\Tests;

use DirectoryTree\Metrics\MetricServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

use function Orchestra\Testbench\laravel_migration_path;

abstract class TestCase extends BaseTestCase
{
    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(laravel_migration_path('/'));
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get the package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [MetricServiceProvider::class];
    }
}
