<?php

namespace DirectoryTree\Metrics\Facades;

use DirectoryTree\Metrics\Measurable;
use DirectoryTree\Metrics\MetricManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \DirectoryTree\Metrics\Metric|null record(Measurable $metric)
 * @method static void commit()
 * @method static void capture()
 * @method static bool isCapturing()
 * @method static void stopCapturing()
 */
class Metrics extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return MetricManager::class;
    }
}
