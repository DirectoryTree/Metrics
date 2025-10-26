<?php

namespace DirectoryTree\Metrics;

use Carbon\CarbonImmutable;
use DirectoryTree\Metrics\Support\Enum;

class JsonMeasurableEncoder implements MeasurableEncoder
{
    /**
     * Encode a metric into a string.
     */
    public function encode(Measurable $metric): string
    {
        $measurable = $metric->measurable();

        return json_encode([
            'name' => Enum::value($metric->name()),
            'category' => Enum::value($metric->category()),
            'year' => $metric->year(),
            'month' => $metric->month(),
            'day' => $metric->day(),
            'hour' => $metric->hour(),
            'model' => $metric->model(),
            'measurable' => $measurable ? get_class($measurable) : null,
            'measurable_key' => $measurable?->getKeyName() ?? null,
            'measurable_id' => $measurable?->getKey() ?? null,
            'additional' => $metric->additional(),
        ]);
    }

    /**
     * Decode a metric string into a metric data.
     */
    public function decode(string $key, int $value): Measurable
    {
        $attributes = json_decode($key, true);

        if ($attributes['measurable'] && class_exists($attributes['measurable'])) {
            /** @var \Illuminate\Database\Eloquent\Model $measurable */
            $measurable = (new $attributes['measurable'])->newFromBuilder([
                $attributes['measurable_key'] => $attributes['measurable_id'],
            ]);
        } else {
            $measurable = null;
        }

        $date = CarbonImmutable::create(
            $attributes['year'],
            $attributes['month'],
            $attributes['day'],
            $attributes['hour'] ?? 0
        );

        return new MetricData(
            name: $attributes['name'],
            category: $attributes['category'],
            value: $value,
            date: $date,
            measurable: $measurable,
            additional: $attributes['additional'] ?? [],
            hourly: $attributes['hour'] ?? false,
            model: $attributes['model'] ?? null,
        );
    }
}
