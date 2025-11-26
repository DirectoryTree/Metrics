<?php

use DirectoryTree\Metrics\Commands\MakeMetricsModel;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

use function Pest\Laravel\artisan;

uses(InteractsWithPublishedFiles::class);

beforeEach(function () {
    $this->files = [
        'app/CustomMetric.php',
        'app/UserMetric.php',
        'app/Models/CustomMetric.php',
        'app/Models/UserMetric.php',
        'app/Metrics/CustomMetric.php',
    ];
});

it('creates a model file with the correct name', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFilenameExists('app/CustomMetric.php');
});

it('displays success message with model name', function () {
    artisan(MakeMetricsModel::class, ['name' => 'UserMetric'])
        ->expectsOutputToContain('created successfully')
        ->assertSuccessful();
});

it('generates model with correct class name', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        'class CustomMetric extends Model',
    ], 'app/CustomMetric.php');
});

it('generates model with correct namespace', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        'namespace App;',
    ], 'app/CustomMetric.php');
});

it('generates model with all required imports', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        'use DirectoryTree\Metrics\MetricBuilder;',
        'use DirectoryTree\Metrics\MetricFactory;',
        'use Illuminate\Database\Eloquent\Factories\HasFactory;',
        'use Illuminate\Database\Eloquent\Model;',
    ], 'app/CustomMetric.php');
});

it('generates model with HasFactory trait', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        'use HasFactory;',
    ], 'app/CustomMetric.php');
});

it('generates model with guarded property', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        'protected $guarded = [];',
    ], 'app/CustomMetric.php');
});

it('generates model with factory property', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        'protected static string $factory = MetricFactory::class;',
    ], 'app/CustomMetric.php');
});

it('generates model with casts method', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        'protected function casts(): array',
        '\'year\' => \'integer\',',
        '\'month\' => \'integer\',',
        '\'day\' => \'integer\',',
        '\'hour\' => \'integer\',',
        '\'value\' => \'integer\',',
    ], 'app/CustomMetric.php');
});

it('generates model with custom query builder method', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        'public function newEloquentBuilder($query): MetricBuilder',
        'return new MetricBuilder($query);',
    ], 'app/CustomMetric.php');
});

it('generates valid php model file', function () {
    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFileContains([
        '<?php',
    ], 'app/CustomMetric.php');
});

it('supports namespaced model names', function () {
    artisan(MakeMetricsModel::class, ['name' => 'Models\CustomMetric'])
        ->assertSuccessful();

    $this->assertFilenameExists('app/Models/CustomMetric.php');
    $this->assertFileContains([
        'namespace App\Models;',
        'class CustomMetric extends Model',
    ], 'app/Models/CustomMetric.php');
});

it('supports deeply namespaced model names', function () {
    artisan(MakeMetricsModel::class, ['name' => 'Metrics\CustomMetric'])
        ->assertSuccessful();

    $this->assertFilenameExists('app/Metrics/CustomMetric.php');
    $this->assertFileContains([
        'namespace App\Metrics;',
        'class CustomMetric extends Model',
    ], 'app/Metrics/CustomMetric.php');
});

it('can generate multiple different metrics models', function () {
    artisan(MakeMetricsModel::class, ['name' => 'UserMetric'])
        ->assertSuccessful();

    artisan(MakeMetricsModel::class, ['name' => 'CustomMetric'])
        ->assertSuccessful();

    $this->assertFilenameExists('app/UserMetric.php');
    $this->assertFilenameExists('app/CustomMetric.php');

    $this->assertFileContains([
        'class UserMetric extends Model',
    ], 'app/UserMetric.php');

    $this->assertFileContains([
        'class CustomMetric extends Model',
    ], 'app/CustomMetric.php');
});
