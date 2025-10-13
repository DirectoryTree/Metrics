<?php

namespace DirectoryTree\Metrics\Jobs;

use DirectoryTree\Metrics\Measurable;
use DirectoryTree\Metrics\MeasurableEncoder;
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
        Collection::make($this->metrics)
            ->groupBy(function (Measurable $data) {
                return app(MeasurableEncoder::class)->encode($data);
            })
            ->each(function (Collection $metrics) {
                if (isset($this->job)) {
                    RecordMetric::dispatch($metrics)
                        ->onQueue($this->queue)
                        ->onConnection($this->connection);
                } else {
                    (new RecordMetric($metrics))->handle();
                }
            });
    }
}
