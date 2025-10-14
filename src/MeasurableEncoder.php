<?php

namespace DirectoryTree\Metrics;

use Carbon\CarbonImmutable;
use DirectoryTree\Metrics\Support\Enum;

class MeasurableEncoder
{
    /**
     * Encode a metric into a string.
     */
    public function encode(Measurable $metric): string
    {
        $model = $metric->measurable();

        return implode('|', [
            Enum::value($metric->name()),
            Enum::value($metric->category()),
            $metric->year(),
            $metric->month(),
            $metric->day(),
            $model ? get_class($model) : null,
            $model?->getKeyName() ?? null,
            $model?->getKey() ?? null,
        ]);
    }

    /**
     * Decode a metric string into a metric data.
     */
    public function decode(string $key, int $value): Measurable
    {
        [
            $name,
            $category,
            $year,
            $month,
            $day,
            $measurableClass,
            $measurableKey,
            $measurableId,
        ] = explode('|', $key);

        if ($measurableClass && class_exists($measurableClass)) {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = (new $measurableClass)->newFromBuilder([
                $measurableKey => $measurableId,
            ]);
        } else {
            $model = null;
        }

        return new MetricData(
            $name,
            $category === '' ? null : $category,
            $value,
            CarbonImmutable::create($year, $month, $day),
            $model
        );
    }
}
