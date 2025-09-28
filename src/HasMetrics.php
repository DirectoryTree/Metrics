<?php

namespace DirectoryTree\Metrics;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasMetrics
{
    /**
     * Get the metrics relationship.
     */
    public function metrics(): MorphMany
    {
        return $this->morphMany(Metric::class, 'model');
    }
}