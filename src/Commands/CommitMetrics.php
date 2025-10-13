<?php

namespace DirectoryTree\Metrics\Commands;

use DirectoryTree\Metrics\MetricManager;
use Illuminate\Console\Command;

class CommitMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:commit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Commit all captured metrics to the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = app(MetricManager::class)->commit();

        if ($count === 0) {
            $this->info('No metrics to commit.');
        } else {
            $this->info("Committed {$count} metric(s).");
        }

        return self::SUCCESS;
    }
}
