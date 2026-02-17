<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Resource;
use Carbon\CarbonImmutable;
use DateTimeInterface;

trait CapacityHelper
{
    /**
     * Resolve a resource's capacity to a numeric value.
     *
     * For HoursPerDay resources, capacity_value represents hours available per day.
     * For Slots resources, capacity_value represents the number of concurrent slots.
     * When no unit or value is set, defaults to 1.0 (single-use resource).
     */
    private function resolveCapacity(Resource $resource): float
    {
        $value = $resource->capacity_value;

        if ($value === null) {
            return 1.0;
        }

        $numericValue = (float) $value;

        return $numericValue > 0 ? $numericValue : 1.0;
    }

    /**
     * Normalize an allocation ratio to a usable float value.
     *
     * When null is provided, returns the resource's full capacity value.
     * Negative values are normalized to 0.0.
     */
    private function normalizeRatio(float|int|string|null $ratio, ?float $capacity = null): float
    {
        if ($ratio === null) {
            return $capacity ?? 1.0;
        }

        $value = (float) $ratio;

        if ($value < 0) {
            return 0.0;
        }

        if ($capacity !== null) {
            return $value * $capacity;
        }

        return $value;
    }

    /**
     * Convert a DateTimeInterface to CarbonImmutable.
     */
    private function toCarbon(DateTimeInterface $dateTime): CarbonImmutable
    {
        if ($dateTime instanceof CarbonImmutable) {
            return $dateTime;
        }

        return CarbonImmutable::instance($dateTime);
    }
}
