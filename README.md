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
php artisan vendor:publish --tag="metrics-config"
```

This will create a `config/metrics.php` file where you can configure queueing behavior:

```php
return [
    // ...

    'queue' => env('METRICS_QUEUE', false) ? [
        'name' => env('METRICS_QUEUE_NAME'),
        'connection' => env('METRICS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    ] : false,
];
```

## Usage

### Recording Metrics

Record a metric using the `Metrics` facade:

```php
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\Facades\Metrics;

Metrics::record(new MetricData('signups'));
```

Or using the `metric` global helper:

```php
metric('signups')->record();
```

Or using the `PendingMetric` class:

```php
use DirectoryTree\Metrics\PendingMetric;

PendingMetric::make('signups')->record();
```

Which ever method you use, metrics are recorded in the same way. Use whichever you prefer.

For the rest of the documentation, we will use the `metric` helper for consistency.

### Metric Values

By default, metrics have a value of `1`. You can specify a custom value:

```php
// Track multiple API calls at once
metric('api_calls')->record(10);

// Track batch job completions
metric('jobs_completed')->record(250);
```

If you record the same metric multiple times, the values will be summed:

```php
metric('logins')->record(); // value: 1
metric('logins')->record(); // value: 1

// Database will contain one metric with value: 2
```

### Recording with Categories

Organize metrics into categories:

```php
// Track API calls by endpoint
metric('api_calls')->category('users')->record();
metric('api_calls')->category('orders')->record();

// Track errors by severity
metric('errors')->category('critical')->record();
metric('errors')->category('warning')->record();

// Track purchases by payment method
metric('purchases')->category('stripe')->record();
metric('purchases')->category('paypal')->record();
```

These will be stored as separate metrics, allowing you to track the same metric across different contexts.

### Recording with Dates

By default, metrics are recorded with today's date. You can specify a custom date:

```php
use Carbon\Carbon;

// Backfill signup data from an import
metric('signups')
    ->date(Carbon::parse('2025-01-15'))
    ->record(50);

// Record yesterday's batch job completions
metric('jobs_completed')
    ->date(Carbon::yesterday())
    ->record(1250);
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

// Track logins per user
metric('logins')->measurable($user)->record();

// Track orders per customer
$customer = Customer::find(1);
metric('orders')->measurable($customer)->record();

// Track API calls per client
$apiClient = ApiClient::find(1);
metric('api_requests')->measurable($apiClient)->record();
```

Query metrics for a model:

```php
// Get total logins for a user
$totalLogins = $user->metrics()
    ->where('name', 'logins')
    ->sum('value');

// Get orders this month for a customer
$ordersThisMonth = $customer->metrics()
    ->where('name', 'orders')
    ->thisMonth()
    ->sum('value');
```

### Capturing & Committing

For high-performance scenarios, you can capture metrics in memory and commit them in batches:

```php
use DirectoryTree\Metrics\Facades\Metrics;

Metrics::capture();

// Record multiple metrics...
metric('signups')->record();
metric('emails_sent')->category('welcome')->record();
metric('signups')->record();

// Commit all captured metrics at once
Metrics::commit();
```

Captured metrics are automatically committed when the application terminates. You can also stop capturing manually:

```php
Metrics::stopCapturing();
```

To enable capturing for your entire application, start capturing in your `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php

use DirectoryTree\Metrics\Facades\Metrics;

class AppServiceProvider extends ServiceProvider
{
    // ...

    public function boot(): void
    {
        Metrics::capture();
    }
}
```

This will batch all metrics recorded during the request and commit them automatically when the application terminates, reducing database queries and improving performance.

#### Example: High-Traffic Controller

```php
public function store(Request $request)
{
    Metrics::capture();

    // Process multiple operations
    $user = User::create($request->validated());
    metric('signups')->record();

    $user->sendWelcomeEmail();
    metric('emails_sent')->category('welcome')->record();

    event(new UserRegistered($user));
    metric('events_dispatched')->record();

    // All metrics committed automatically at end of request
    return response()->json($user);
}
```

#### Example: Batch Job Processing

```php
public function handle()
{
    Metrics::capture();

    Order::pending()->chunk(100, function ($orders) {
        foreach ($orders as $order) {
            $order->process();

            metric('orders_processed')->record();
        }
    });

    Metrics::commit(); // Batch commit all metrics
}
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
// Get today's signups
$signups = Metric::today()
    ->where('name', 'signups')
    ->sum('value');

// Get this week's purchases
$purchases = Metric::thisWeek()
    ->where('name', 'purchases')
    ->sum('value');

// Get API usage by endpoint this month
$apiUsage = Metric::thisMonth()
    ->where('name', 'api_calls')
    ->get()
    ->groupBy('category')
    ->map->sum('value');

// Count unique metrics recorded today
$count = Metric::today()->count();
```

#### Example: Compare Growth

```php
// Compare this month vs last month signups
$thisMonth = Metric::thisMonth()
    ->where('name', 'signups')
    ->sum('value');

$lastMonth = Metric::lastMonth()
    ->where('name', 'signups')
    ->sum('value');

$growth = (($thisMonth - $lastMonth) / $lastMonth) * 100;
```

#### Example: Calculate Error Rate

```php
// Get error rate for the year
$errors = Metric::thisYear()
    ->where('name', 'errors')
    ->sum('value');

$requests = Metric::thisYear()
    ->where('name', 'api_calls')
    ->sum('value');

$errorRate = ($errors / $requests) * 100;
```

## Testing

Metrics includes a fake implementation for testing:

```php
use DirectoryTree\Metrics\Measurable;
use DirectoryTree\Metrics\Facades\Metrics;

public function test_user_signup_records_metric()
{
    Metrics::fake();

    // Your code that records metrics...
    $this->post('register', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    // Assert metrics were recorded
    Metrics::assertRecorded('signups');
}

public function test_api_call_records_metric_with_endpoint()
{
    Metrics::fake();

    $this->getJson('api/users');

    // Assert metrics were recorded with a closure
    Metrics::assertRecorded(fn (Measurable $metric) =>
        $metric->name() === 'api_calls' &&
        $metric->category() === 'users'
    );
}

public function test_failed_login_records_metric()
{
    Metrics::fake();

    $this->post('login', [
        'email' => 'wrong@example.com',
        'password' => 'wrong',
    ]);

    // Assert metrics were not recorded
    Metrics::assertNotRecorded('logins');

    // Assert failed login was recorded
    Metrics::assertRecorded('failed_logins');
}

public function test_purchase_records_metric_for_user()
{
    Metrics::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->post('/purchases', [
        'product_id' => 1,
    ]);

    // Assert metrics were recorded with model
    Metrics::assertRecorded(fn ($metric) =>
        $metric->name() === 'purchases' &&
        $metric->measurable()?->is($user)
    );
}

public function test_batch_job_records_metrics()
{
    Metrics::fake();

    // Run your batch job
    Artisan::call('orders:process');

    // Assert metrics were recorded a specific number of times
    Metrics::assertRecordedTimes('orders_processed', 100);
}
```

Access recorded metrics in tests:

```php
Metrics::fake();

metric('api_calls')->category('users')->record();

// Get all recorded metrics
$all = Metrics::recorded();

// Get metrics by name
$apiCalls = Metrics::recorded('api_calls');

// Get metrics with a closure
$userEndpoint = Metrics::recorded(fn ($metric) =>
    $metric->category() === 'users'
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
