<?php

namespace DirectoryTree\Metrics\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeMetricsModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:model {name : The name of the metrics model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new metrics model';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');

        $this->writeModel($files, $name);

        $this->components->info(sprintf('Model [%s] created successfully.', $this->getModelFileName($name)));

        return self::SUCCESS;
    }

    /**
     * Write the model file to disk.
     */
    protected function writeModel(Filesystem $files, string $name): void
    {
        $file = $this->getModelPath($name);

        $files->ensureDirectoryExists(dirname($file));

        $files->put($file, $this->populateStub($name));
    }

    /**
     * Populate the stub with the model name and namespace.
     */
    protected function populateStub(string $name): string
    {
        $stub = $this->getStub();

        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);

        return str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );
    }

    /**
     * Get the model file path.
     */
    protected function getModelPath(string $name): string
    {
        return app_path($this->getModelFileName($name));
    }

    /**
     * Get the model file name.
     */
    protected function getModelFileName(string $name): string
    {
        return str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the class name from the model name.
     */
    protected function getClassName(string $name): string
    {
        return class_basename($name);
    }

    /**
     * Get the namespace from the model name.
     */
    protected function getNamespace(string $name): string
    {
        $namespace = 'App';

        if (Str::contains($name, '\\')) {
            $parts = explode('\\', $name);
            array_pop($parts);
            $namespace .= '\\'.implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the stub file contents.
     */
    protected function getStub(): string
    {
        return file_get_contents(__DIR__.'/../../stubs/metrics.model.stub');
    }
}
