<?php

namespace App\Enums;

/**
 * The lifecycle states a ticket can be in.
 */
enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * Every allowed value, for validation rules and messages.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
