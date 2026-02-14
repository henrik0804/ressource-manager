<?php

declare(strict_types=1);

namespace App\Enums;

enum AssignmentSource: string
{
    case Manual = 'manual';
    case Automated = 'automated';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manuell',
            self::Automated => 'Automatisch',
        };
    }
}
