<?php

namespace DirectoryTree\Metrics;

use Illuminate\Database\Eloquent\Factories\Factory;

class MetricFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Metric::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $datetime = now();

        return [
            'name' => $this->faker->word(),
            'category' => null,
            'year' => $datetime->year,
            'month' => $datetime->month,
            'day' => $datetime->day,
            'measurable_type' => null,
            'measurable_id' => null,
            'value' => 1,
        ];
    }
}
