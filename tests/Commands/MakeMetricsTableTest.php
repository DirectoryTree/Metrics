<?php

use DirectoryTree\Metrics\Commands\MakeMetricsTable;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

uses(InteractsWithPublishedFiles::class);

it('creates a migration file with the correct name', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'test_metrics'])
        ->assertSuccessful();

    $this->assertMigrationFileExists('*_create_test_metrics_table.php');
});

it('displays success message with migration name', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'user_metrics'])
        ->expectsOutputToContain('created successfully')
        ->assertSuccessful();
});

it('generates migration with correct table name in schema', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'custom_metrics'])
        ->assertSuccessful();

    $this->assertMigrationFileContains([
        "Schema::create('custom_metrics'",
        "Schema::dropIfExists('custom_metrics'",
    ], '*_create_custom_metrics_table.php');
});

it('generates migration with all required columns', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'test_metrics'])
        ->assertSuccessful();

    $this->assertMigrationFileContains([
        '$table->id()',
        '$table->string(\'name\')->index()',
        '$table->string(\'category\')->nullable()->index()',
        '$table->nullableMorphs(\'measurable\')',
        '$table->unsignedSmallInteger(\'year\')',
        '$table->unsignedTinyInteger(\'month\')',
        '$table->unsignedTinyInteger(\'day\')',
        '$table->unsignedTinyInteger(\'hour\')->nullable()',
        '$table->unsignedInteger(\'value\')',
        '$table->timestamps()',
    ], '*_create_test_metrics_table.php');
});

it('generates migration with correct index', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'test_metrics'])
        ->assertSuccessful();

    $this->assertMigrationFileContains([
        "\$table->index(['year', 'month', 'day', 'hour'])",
    ], '*_create_test_metrics_table.php');
});

it('generates migration with up and down methods', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'test_metrics'])
        ->assertSuccessful();

    $this->assertMigrationFileContains([
        'public function up(): void',
        'public function down(): void',
    ], '*_create_test_metrics_table.php');
});

it('creates migration file with timestamp prefix', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'test_metrics'])
        ->assertSuccessful();

    $this->assertMigrationFileExists('*_create_test_metrics_table.php');
});

it('can generate multiple different metrics tables', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'user_metrics'])
        ->assertSuccessful();

    sleep(1); // Ensure different timestamp

    $this->artisan(MakeMetricsTable::class, ['name' => 'custom_metrics'])
        ->assertSuccessful();

    $this->assertMigrationFileExists('*_create_user_metrics_table.php');
    $this->assertMigrationFileExists('*_create_custom_metrics_table.php');

    $this->assertMigrationFileContains([
        "Schema::create('user_metrics'",
    ], '*_create_user_metrics_table.php');

    $this->assertMigrationFileContains([
        "Schema::create('custom_metrics'",
    ], '*_create_custom_metrics_table.php');
});

it('generates valid php migration file', function () {
    $this->artisan(MakeMetricsTable::class, ['name' => 'test_metrics'])
        ->assertSuccessful();

    $this->assertMigrationFileContains([
        '<?php',
        'use Illuminate\Database\Migrations\Migration',
        'use Illuminate\Database\Schema\Blueprint',
        'use Illuminate\Support\Facades\Schema',
        'return new class extends Migration',
    ], '*_create_test_metrics_table.php');
});
