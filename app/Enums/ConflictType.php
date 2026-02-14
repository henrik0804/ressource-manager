<?php

declare(strict_types=1);

namespace App\Enums;

enum ConflictType: string
{
    case DoubleBooked = 'double_booked';
    case Overloaded = 'overloaded';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::DoubleBooked => 'Doppelbuchung',
            self::Overloaded => 'Überlastung',
            self::Unavailable => 'Nicht verfügbar',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DoubleBooked => 'Die Ressource ist in diesem Zeitraum bereits einer anderen Aufgabe zugewiesen.',
            self::Overloaded => 'Die Gesamtauslastung der Ressource übersteigt die verfügbare Kapazität.',
            self::Unavailable => 'Die Ressource ist in diesem Zeitraum als abwesend eingetragen.',
        };
    }
}
