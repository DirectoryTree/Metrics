<?php

namespace DirectoryTree\Metrics;

use DirectoryTree\Metrics\Commands\CommitMetrics;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
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

        $this->app->bind(MeasurableEncoder::class, JsonMeasurableEncoder::class);

        $this->app->scoped(MetricManager::class, DatabaseMetricManager::class);
        $this->app->scoped(MetricRepository::class, function (Application $app) {
            return match ($app->make('config')->get('metrics.driver')) {
                'redis' => $app->make(RedisMetricRepository::class),
                default => $app->make(ArrayMetricRepository::class),
            };
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(CommitMetrics::class);
        }

        if ($this->app->make('config')->get('metrics.auto_commit', true)) {
            $this->app->terminating(function (Application $app) {
                $app->make(MetricManager::class)->commit();
            });

            Queue::looping(function () {
                App::make(MetricManager::class)->commit();
            });
        }

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
