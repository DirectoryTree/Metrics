<?php

namespace DirectoryTree\Metrics\Support;

use BackedEnum;
use UnitEnum;

class Enum
{
    /**
     * Get the value of the enum.
     */
    public static function value(mixed $value, mixed $default = null): string|int|null
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            default => $value ?? value($default),
        };
    }
}
