<?php

declare(strict_types=1);

namespace App\Enums;

enum QualificationLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Expert = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Anfänger',
            self::Intermediate => 'Fortgeschritten',
            self::Advanced => 'Erfahren',
            self::Expert => 'Experte',
        };
    }
}
