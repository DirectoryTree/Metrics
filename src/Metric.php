<?php

namespace DirectoryTree\Metrics;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

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
}
