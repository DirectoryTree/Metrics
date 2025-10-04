<?php

namespace DirectoryTree\Metrics;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class MetricServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/metrics.php', 'metrics'
        );

        $this->app->singleton(MetricManager::class, DatabaseMetricManager::class);
        $this->app->singleton(MetricRepository::class, ArrayMetricRepository::class);

        $this->app->terminating(function (Application $app) {
            $app->make(MetricManager::class)->commit();
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $publish = method_exists($this, 'publishesMigrations')
            ? 'publishesMigrations'
            : 'publishes';

        $this->{$publish}([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'metrics-migrations');

        $this->publishes([
            __DIR__.'/../config/metrics.php' => $this->app['path.config'].DIRECTORY_SEPARATOR.'metrics.php',
        ], 'metrics-config');
    }
}
