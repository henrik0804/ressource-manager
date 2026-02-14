<?php

declare(strict_types=1);

namespace App\Enums;

enum EffortUnit: string
{
    case Hours = 'hours';
    case Days = 'days';

    public function label(): string
    {
        return match ($this) {
            self::Hours => 'Stunden',
            self::Days => 'Tage',
        };
    }

    /**
     * Convert a value in this unit to hours.
     */
    public function toHours(float $value): float
    {
        return match ($this) {
            self::Hours => $value,
            self::Days => $value * 8.0,
        };
    }
}
