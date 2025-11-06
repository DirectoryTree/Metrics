<?php

namespace DirectoryTree\Metrics\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeMetricsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:table {name : The name of the metrics table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new metrics table migration';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $files): int
    {
        $table = $this->argument('name');

        $this->writeMigration($files, $table);

        $this->components->info(sprintf('Migration [%s] created successfully.', $this->getMigrationFileName($table)));

        return self::SUCCESS;
    }

    /**
     * Write the migration file to disk.
     */
    protected function writeMigration(Filesystem $files, string $table): void
    {
        $file = $this->getMigrationPath($table);

        $files->ensureDirectoryExists(dirname($file));

        $files->put($file, $this->populateStub($table));
    }

    /**
     * Populate the stub with the table name.
     */
    protected function populateStub(string $table): string
    {
        $stub = $this->getStub();

        return str_replace('{{ table }}', $table, $stub);
    }

    /**
     * Get the migration file path.
     */
    protected function getMigrationPath(string $table): string
    {
        return database_path('migrations/'.$this->getMigrationFileName($table));
    }

    /**
     * Get the migration file name.
     */
    protected function getMigrationFileName(string $table): string
    {
        return date('Y_m_d_His').'_create_'.$table.'_table.php';
    }

    /**
     * Get the stub file contents.
     */
    protected function getStub(): string
    {
        return file_get_contents(__DIR__.'/../../stubs/metrics.table.stub');
    }
}
