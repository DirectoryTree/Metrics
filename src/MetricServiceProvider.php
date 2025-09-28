<?php

namespace DirectoryTree\Metrics;

use Illuminate\Support\ServiceProvider;

class MetricServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(MetricRepository::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations/2025_09_28_131354_create_metrics_table.php' => database_path('migrations/2025_09_28_131354_create_metrics_table.php'),
        ], 'metrics-migrations');
    }
}
