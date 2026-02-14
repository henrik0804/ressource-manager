<?php

declare(strict_types=1);

namespace App\Enums;

enum AssigneeStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ausstehend',
            self::Accepted => 'Angenommen',
            self::InProgress => 'In Bearbeitung',
            self::Done => 'Erledigt',
            self::Rejected => 'Abgelehnt',
        };
    }
}
