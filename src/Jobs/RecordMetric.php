<?php

namespace DirectoryTree\Metrics\Jobs;

use DirectoryTree\Metrics\DatabaseMetricManager;
use DirectoryTree\Metrics\Measurable;
use DirectoryTree\Metrics\Metric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class RecordMetric implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Constructor.
     */
    public function __construct(
        /** @var Collection<Measurable>|Measurable */
        public Collection|Measurable $metrics
    ) {}

    /**
     * Record the metric.
     */
    public function handle(): void
    {
        $metrics = Collection::wrap($this->metrics);

        /** @var Measurable $metric */
        if (! $metric = $metrics->first()) {
            return;
        }

        $value = $metrics->sum(
            fn (Measurable $metric) => $metric->value()
        );

        /** @var Model $model */
        $model = transform($metric->model() ?? DatabaseMetricManager::$model, fn (string $model) => new $model);

        $model->getConnection()->transaction(function () use ($metric, $value, $model) {
            $instance = $model->newQuery()->firstOrCreate([
                ...$this->getAdditionalAttributes($metric, $model),
                'name' => $metric->name(),
                'category' => $metric->category(),
                'year' => $metric->year(),
                'month' => $metric->month(),
                'day' => $metric->day(),
                ...(is_null($metric->hour()) ? [] : ['hour' => $metric->hour()]),
                'measurable_type' => $metric->measurable()?->getMorphClass(),
                'measurable_id' => $metric->measurable()?->getKey(),
            ], ['value' => 0]);

            $model->newQuery()
                ->whereKey($instance)
                ->increment('value', $value);
        });
    }

    /**
     * Get the additional attributes for the metric.
     */
    protected function getAdditionalAttributes(Measurable $metric, Model $model): array
    {
        return Collection::make($metric->additional())->mapWithKeys(fn (mixed $value, mixed $key) => [
            // If the model has a cast for the key, we can assume the model will
            // handle the value correctly. If not, and the value is an array,
            // we should encode it as JSON before attempting to store it.
            $key => $model->hasCast($key) ? $value : (is_array($value) ? json_encode($value) : $value),
        ])->all();
    }
}
