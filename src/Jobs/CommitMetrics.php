<?php

namespace DirectoryTree\Metrics\Jobs;

use DirectoryTree\Metrics\Measurable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;

class CommitMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Constructor.
     */
    public function __construct(
        /** @var Measurable[] */
        public array $metrics,
    ) {}

    /**
     * Commit all metrics to the database.
     */
    public function handle(): void
    {
        Collection::make($this->metrics)->each(function (Measurable $metric) {
            if (isset($this->job)) {
                RecordMetric::dispatch($metric)
                    ->onQueue($this->queue)
                    ->onConnection($this->connection);
            } else {
                (new RecordMetric($metric))->handle();
            }
        });
    }
}
