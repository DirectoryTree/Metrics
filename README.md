<p align="center">
<img src="https://github.com/DirectoryTree/Metrics/blob/master/art/logo.svg" width="250">
</p>

<p align="center">
A simple and elegant way to record metrics in your Laravel application.
</p>

<p align="center">
<a href="https://github.com/directorytree/metrics/actions" target="_blank"><img src="https://img.shields.io/github/actions/workflow/status/directorytree/metrics/run-tests.yml?branch=master&style=flat-square"/></a>
<a href="https://packagist.org/packages/directorytree/metrics" target="_blank"><img src="https://img.shields.io/packagist/v/directorytree/metrics.svg?style=flat-square"/></a>
<a href="https://packagist.org/packages/directorytree/metrics" target="_blank"><img src="https://img.shields.io/packagist/dt/directorytree/metrics.svg?style=flat-square"/></a>
<a href="https://packagist.org/packages/directorytree/metrics" target="_blank"><img src="https://img.shields.io/packagist/l/directorytree/metrics.svg?style=flat-square"/></a>
</p>

---

Metrics provides a simple, elegant way to record and query metrics in your Laravel application. Track page views, API calls, user signups, or any other countable events with ease.

## Index

- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
- [Usage](#usage)
  - [Recording Metrics](#recording-metrics)
  - [Recording with Categories](#recording-with-categories)
  - [Recording with Dates](#recording-with-dates)
  - [Recording for Models](#recording-for-models)
  - [Capturing & Committing](#capturing--committing)
  - [Querying Metrics](#querying-metrics)
- [Testing](#testing)
- [Extending & Customizing](#extending--customizing)

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

## Installation

You can install the package via composer:

```bash
composer require directorytree/metrics
```

Then, publish the migrations:

```bash
php artisan vendor:publish --tag="metrics-migrations"
```

Finally, run the migrations:

```bash
php artisan migrate
```

## Setup

Optionally, you can publish the configuration file:

```bash
php artisan vendor:publish --provider="DirectoryTree\Metrics\MetricServiceProvider"
```

This will create a `config/metrics.php` file where you can configure queueing behavior:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Queue Metric Recording
    |--------------------------------------------------------------------------
    |
    | When enabled, recorded metrics will be dispatched in a queued job to
    | be saved. This is useful for high-traffic applications where recording
    | a large number of metrics could impact performance. When disabled,
    | metrics will be recorded synchronously.
    |
    */

    'queue' => env('METRICS_QUEUE', false),
];
```

## Usage

### Recording Metrics

Record a metric using the `Metrics` facade:

```php
use DirectoryTree\Metrics\Facades\Metrics;
use DirectoryTree\Metrics\MetricData;

Metrics::record(new MetricData('page_views'));
```

By default, metrics have a value of `1`. You can specify a custom value:

```php
Metrics::record(new MetricData('api_calls', value: 5));
```

If you record the same metric multiple times, the values will be summed:

```php
Metrics::record(new MetricData('page_views')); // value: 1
Metrics::record(new MetricData('page_views')); // value: 1

// Database will contain one metric with value: 2
```

### Recording with Categories

Organize metrics into categories:

```php
Metrics::record(new MetricData('page_views', 'marketing'));
Metrics::record(new MetricData('page_views', 'analytics'));
```

These will be stored as separate metrics, allowing you to track the same metric across different contexts.

### Recording with Dates

By default, metrics are recorded with today's date. You can specify a custom date:

```php
use Carbon\Carbon;

Metrics::record(new MetricData(
    name: 'page_views',
    date: Carbon::yesterday()
));
```

### Recording for Models

Associate metrics with Eloquent models using the `HasMetrics` trait:

```php
use DirectoryTree\Metrics\HasMetrics;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasMetrics;
}
```

Then record metrics for a specific model:

```php
$user = User::find(1);

Metrics::record(new MetricData('logins', measurable: $user));
```

Query metrics for a model:

```php
$user->metrics()->where('name', 'logins')->sum('value');
```

### Capturing & Committing

For high-performance scenarios, you can capture metrics in memory and commit them in batches:

```php
Metrics::capture();

// Record multiple metrics...
Metrics::record(new MetricData('page_views'));
Metrics::record(new MetricData('api_calls'));
Metrics::record(new MetricData('page_views'));

// Commit all captured metrics at once
Metrics::commit();
```

Captured metrics are automatically committed when the application terminates. You can also stop capturing manually:

```php
Metrics::stopCapturing();
```

### Querying Metrics

The `Metric` model includes a powerful query builder with date filtering methods:

```php
use DirectoryTree\Metrics\Metric;

// Get today's metrics
$metrics = Metric::today()->get();

// Get this week's metrics
$metrics = Metric::thisWeek()->get();

// Get this month's metrics
$metrics = Metric::thisMonth()->get();

// Get this year's metrics
$metrics = Metric::thisYear()->get();

// Get yesterday's metrics
$metrics = Metric::yesterday()->get();

// Get last week's metrics
$metrics = Metric::lastWeek()->get();

// Get last month's metrics
$metrics = Metric::lastMonth()->get();

// Get last year's metrics
$metrics = Metric::lastYear()->get();

// Get metrics between specific dates
$metrics = Metric::betweenDates(
    Carbon::parse('2025-01-01'),
    Carbon::parse('2025-12-31')
)->get();

// Get metrics on a specific date
$metrics = Metric::onDate(Carbon::parse('2025-10-15'))->get();
```

Chain with standard Eloquent methods:

```php
// Get total page views for this month
$total = Metric::thisMonth()
    ->where('name', 'page_views')
    ->sum('value');

// Get all marketing metrics for this week
$metrics = Metric::thisWeek()
    ->where('category', 'marketing')
    ->get();

// Count unique metrics recorded today
$count = Metric::today()->count();
```

## Testing

Metrics includes a fake implementation for testing:

```php
use DirectoryTree\Metrics\Facades\Metrics;
use DirectoryTree\Metrics\MetricData;

public function test_metrics_are_recorded()
{
    Metrics::fake();

    // Your code that records metrics...
    Metrics::record(new MetricData('page_views'));

    // Assert metrics were recorded
    Metrics::assertRecorded('page_views');

    // Assert metrics were recorded with a closure
    Metrics::assertRecorded(fn ($metric) =>
        $metric->name() === 'page_views' &&
        $metric->category() === 'marketing'
    );

    // Assert metrics were not recorded
    Metrics::assertNotRecorded('api_calls');

    // Assert nothing was recorded
    Metrics::assertNothingRecorded();

    // Assert metrics were recorded a specific number of times
    Metrics::assertRecordedTimes('page_views', 3);
}
```

Access recorded metrics in tests:

```php
Metrics::fake();

Metrics::record(new MetricData('page_views', 'marketing'));

// Get all recorded metrics
$all = Metrics::recorded();

// Get metrics by name
$pageViews = Metrics::recorded('page_views');

// Get metrics with a closure
$marketing = Metrics::recorded(fn ($metric) =>
    $metric->category() === 'marketing'
);
```

## Extending & Customizing

### Custom Metric Manager

Create your own metric manager by implementing the `MetricManager` interface:

```php
namespace App\Metrics;

use DirectoryTree\Metrics\Measurable;
use DirectoryTree\Metrics\MetricManager;

class CustomMetricManager implements MetricManager
{
    public function record(Measurable $metric): void
    {
        // Your custom recording logic...
    }

    public function commit(): void
    {
        // Your custom commit logic...
    }

    public function capture(): void
    {
        // Your custom capture logic...
    }

    public function isCapturing(): bool
    {
        // Your custom capturing check...
    }

    public function stopCapturing(): void
    {
        // Your custom stop capturing logic...
    }
}
```

Then bind it in your `AppServiceProvider`:

```php
use App\Metrics\CustomMetricManager;
use DirectoryTree\Metrics\MetricManager;

public function register(): void
{
    $this->app->singleton(MetricManager::class, CustomMetricManager::class);
}
```

### Custom Metric Repository

Create a custom repository for storing captured metrics:

```php
namespace App\Metrics;

use DirectoryTree\Metrics\Measurable;
use DirectoryTree\Metrics\MetricRepository;

class CustomMetricRepository implements MetricRepository
{
    public function add(Measurable $metric): void
    {
        // Your custom add logic...
    }

    public function all(): array
    {
        // Your custom retrieval logic...
    }

    public function flush(): void
    {
        // Your custom flush logic...
    }
}
```

Then bind it in your `AppServiceProvider`:

```php
use App\Metrics\CustomMetricRepository;
use DirectoryTree\Metrics\MetricRepository;

public function register(): void
{
    $this->app->singleton(MetricRepository::class, CustomMetricRepository::class);
}
```
