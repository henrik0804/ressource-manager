<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Geplant',
            self::InProgress => 'In Bearbeitung',
            self::Blocked => 'Blockiert',
            self::Done => 'Erledigt',
        };
    }
}
