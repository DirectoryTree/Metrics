<?php

namespace DirectoryTree\Metrics;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'day' => 'integer',
            'value' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): MetricBuilder
    {
        return new MetricBuilder($query);
    }
}
