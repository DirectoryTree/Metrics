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

Metrics provides a simple, elegant way to record and query metrics in your Laravel application.

Track page views, API calls, user signups, or any other countable events.

## Index

- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
  - [Using the Redis Driver](#using-the-redis-driver)
- [Usage](#usage)
  - [Recording Metrics](#recording-metrics)
  - [Recording with Values](#recording-with-values)
  - [Recording with Categories](#recording-with-categories)
  - [Recording with Dates](#recording-with-dates)
  - [Recording Hourly Metrics](#recording-hourly-metrics)
  - [Recording for Models](#recording-for-models)
  - [Recording with Custom Attributes](#recording-with-custom-attributes)
  - [Capturing & Committing](#capturing--committing)
  - [Querying Metrics](#querying-metrics)
- [Testing](#testing)
- [Extending & Customizing](#extending--customizing)
  - [Custom Metric Models](#custom-metric-models)
  - [Custom Metric Repository](#custom-metric-repository)
  - [Custom Metric Manager](#custom-metric-manager)

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

## Installation

You may install the package via composer:

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

Optionally, you may publish the configuration file:

```bash
php artisan vendor:publish --tag="metrics-config"
```

This will create a `config/metrics.php` file where you may configure the driver and queueing behavior.

### Using the Redis Driver

For distributed applications or high-traffic scenarios, you may use the `redis` driver to store captured metrics in Redis before committing them to the database in batches.

First, set the driver to `redis` in your configuration:

```php
// config/metrics.php

return [
    'driver' => 'redis',
    
    // ...
];
```

Or via environment variable:

```env
METRICS_DRIVER=redis
```

Then, you may schedule the `metrics:commit` command to periodically commit metrics from Redis to your database:

> [!important]
> Remember to disable `auto_commit` in the configuration file if you plan on committing metrics using the commit command.

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    $schedule->command('metrics:commit')->hourly();
}
```

You may also run the command manually:

```bash
php artisan metrics:commit
```

This approach provides reduced database load since metrics can be committed in bulk at an expected interval instead of at the end of the request life-cycle.

The Redis driver uses a hash to store pending metrics with a configurable TTL (default of 1 day). This ensures metrics are eventually committed even if the scheduled command fails temporarily.

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

For the rest of the documentation, we will use the `metric` helper for consistency and brevity.

### Recording with Values

By default, metrics have a value of `1`. You may specify a custom value in the `record` method:

```php
// Track multiple API calls at once
metric('api:requests')->record(10);

// Track batch job completions
metric('jobs:completed')->record(250);
```

If you record the same metric multiple times, the values will be summed:

```php
metric('auth:logins')->record(); // value: 1
metric('auth:logins')->record(); // value: 1

// Database will contain one metric with value: 2
```

### Recording with Categories

Organize metrics into categories:

```php
// Track API calls by endpoint
metric('api:requests')->category('users')->record();
metric('api:requests')->category('orders')->record();

// Track errors by severity
metric('app:errors')->category('critical')->record();
metric('app:errors')->category('warning')->record();

// Track purchases by payment method
metric('purchases')->category('stripe')->record();
metric('purchases')->category('paypal')->record();
```

These will be stored as separate metrics, allowing you to track the same metric across different contexts.

### Recording with Dates

By default, metrics are recorded with today's date. You may specify a custom date using the `date` method:

```php
use Carbon\Carbon;

// Backfill signup data from an import
metric('signups')
    ->date(Carbon::parse('2025-01-15'))
    ->record(50);

// Record yesterday's batch job completions
metric('jobs:completed')
    ->date(Carbon::yesterday())
    ->record(1250);
```

### Recording Hourly Metrics

By default, metrics are recorded at the **daily** level. For metrics that require hour-level granularity, you may use the `hourly()` method:

```php
// Track API requests by hour
metric('api:requests')
    ->hourly()
    ->record();
```

Hourly metrics include the hour (0-23) in addition to the year, month, and day, allowing you to track metrics at a more granular level:

```php
use Carbon\Carbon;

// Record API requests for a specific hour
metric('api:requests')
    ->date(Carbon::parse('2025-10-19 14:30:00'))
    ->hourly()
    ->record();

// This will be stored with hour = 14
```

Hourly metrics are stored separately from daily metrics, even for the same metric name:

```php
metric('page:views')->record();         // Daily metric (hour = null)
metric('page:views')->hourly()->record(); // Hourly metric (hour = current hour)
```

You can query hourly metrics using the `thisHour()`, `lastHour()`, and `onDateTime()` methods:

```php
use DirectoryTree\Metrics\Metric;

// Get metrics for this hour
$metrics = Metric::thisHour()->get();

// Get metrics for last hour
$metrics = Metric::lastHour()->get();

// Get metrics for a specific date and hour
$metrics = Metric::onDateTime(Carbon::parse('2025-10-19 14:00:00'))->get();

// Get API requests for the current hour
$requests = Metric::thisHour()
    ->where('name', 'api:requests')
    ->sum('value');
```

> [!tip]
> Use hourly metrics sparingly, as they create 24x more database rows than daily metrics. Reserve hourly tracking for metrics that genuinely benefit from hour-level granularity.

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
// Track logins per user
metric('auth:logins')
    ->measurable(Auth::user())
    ->record();

// Track orders per customer
metric('orders')
    ->measurable(Customer::find(...))
    ->record();

// Track API calls per client
metric('api:requests')
    ->measurable(ApiClient::find(...))
    ->record();
```

Query metrics for a model:

```php
// Get total logins for a user
$totalLogins = $user->metrics()
    ->where('name', 'auth:logins')
    ->sum('value');

// Get orders this month for a customer
$ordersThisMonth = $customer->metrics()
    ->where('name', 'orders')
    ->thisMonth()
    ->sum('value');
```

### Recording with Custom Attributes

Store additional context with your metrics by adding custom attributes. This is useful for segmenting metrics by various dimensions like source, country, device type, or any other custom data.

First, create a migration to add custom columns to the `metrics` table:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('metrics', function (Blueprint $table) {
    $table->string('source')->nullable()->index();
    $table->string('country')->nullable()->index();
    $table->string('device')->nullable();
});
```

Then, use the `with()` method to record metrics with custom attributes:

```php
// Track post views with traffic source
metric('views')
    ->measurable(Post::find(1))
    ->with(['source' => 'google'])
    ->record();

// Track conversions with multiple attributes
metric('conversions')
    ->with([
        'source' => 'facebook',
        'country' => 'US',
        'device' => 'mobile',
    ])
    ->record();

// Track API requests for a user within a team
metric('api:requests')
    ->measurable(Auth::user())
    ->with(['team_id' => 123])
    ->record();
```

Custom attributes are included in the metric's uniqueness check, meaning metrics with different attribute values are stored separately:

```php
metric('page:views')->with(['source' => 'google'])->record();   // Creates metric #1
metric('page:views')->with(['source' => 'facebook'])->record(); // Creates metric #2
metric('page:views')->with(['source' => 'google'])->record();   // Increments metric #1
```

This allows you to segment and analyze metrics by any dimension:

```php
// Get page views by source
$googleViews = Metric::where('name', 'page:views')
    ->where('source', 'google')
    ->sum('value');

// Get conversions by country this month
$conversions = Metric::thisMonth()
    ->where('name', 'conversions')
    ->get()
    ->groupBy('country')
    ->map->sum('value');

// Get mobile vs desktop traffic
$mobileViews = Metric::today()
    ->where('name', 'page:views')
    ->where('device', 'mobile')
    ->sum('value');
```

You can also use custom attributes with the `MetricData` class:

```php
use DirectoryTree\Metrics\MetricData;
use DirectoryTree\Metrics\Facades\Metrics;

Metrics::record(new MetricData(
    name: 'page:views',
    additional: [
        'source' => 'google',
        'country' => 'US',
    ]
));
```

Or with the `PendingMetric` class:

```php
use DirectoryTree\Metrics\PendingMetric;

PendingMetric::make('page:views')
    ->with(['source' => 'google', 'country' => 'US'])
    ->record();
```

> [!important]
> Core metric attributes (`name`, `category`, `year`, `month`, `day`, `measurable_type`, `measurable_id`, `value`) cannot be overridden via custom attributes. They are protected and will always use the values set through their respective methods.

### Capturing & Committing

For high-performance scenarios, you may capture metrics in memory and commit them in batches:

```php
use DirectoryTree\Metrics\Facades\Metrics;

Metrics::capture();

// Record multiple metrics...
metric('signups')->record();
metric('notifications:sent')->category('welcome')->record();
metric('signups')->record();

// Commit all captured metrics at once
Metrics::commit();
```

Captured metrics are automatically committed when the application terminates. You may also stop capturing manually:

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
    ->where('name', 'api:requests')
    ->get()
    ->groupBy('category')
    ->map->sum('value');

// Count unique metrics recorded today
$count = Metric::today()->count();
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
        $metric->name() === 'api:requests' &&
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
    Metrics::assertNotRecorded('auth:logins');

    // Assert failed login was recorded
    Metrics::assertRecorded('auth:attempts');
}

public function test_purchase_records_metric_for_user()
{
    Metrics::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->post('purchases', [
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
    Metrics::assertRecordedTimes('orders:processed', 100);
}
```

Access recorded metrics in tests:

```php
Metrics::fake();

metric('api:requests')->category('users')->record();

// Get all recorded metrics
$all = Metrics::recorded();

// Get metrics by name
$apiCalls = Metrics::recorded('api:requests');

// Get metrics with a closure
$userEndpoint = Metrics::recorded(fn ($metric) =>
    $metric->category() === 'users'
);
```

## Extending & Customizing

### Custom Metric Models

By default, metrics are stored using the included `DirectoryTree\Metrics\Metric` model. You may use a custom model globally or per-metric.

#### Global Custom Model

To use a custom metric model for all metrics, you may create your own model instance with the below requirements:

1. Be fully unguarded (so the model can `fill`'ed appropriately)
2. Include the same columns as the default `metrics` table
3. Include the same casts as the default `Metric` model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomMetric extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'day' => 'integer',
            'hour' => 'integer',
            'value' => 'integer',
        ];
    }
}
```

Once you have created your custom model, you may set it as the default using the `useModel()` method on the `DatabaseMetricManager`:

```php
use App\Models\CustomMetric;
use DirectoryTree\Metrics\DatabaseMetricManager;

// In your AppServiceProvider boot method
DatabaseMetricManager::useModel(CustomMetric::class);
```

#### Per-Metric Custom Model

To use different metric models for different metrics, use the `model()` method on `PendingMetric`:

```php
use App\Models\ApiMetric;
use App\Models\UserMetric;

// Store API metrics in a separate table
metric('requests')
    ->model(ApiMetric::class)
    ->record();

// Store user metrics in another table
metric('user:logins')
    ->model(UserMetric::class)
    ->with(['user_id' => Auth::id()])
    ->record();

// Use the default model
metric('page:views')->record();
```

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
