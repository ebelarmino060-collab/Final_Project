<?php

namespace App\Policies\V1;

use App\Models\Ticket;
use App\Models\User;

/**
 * Authorization rules for tickets. Each method returns true (allowed) or false (denied).
 */
class TicketPolicy
{
    /**
     * May this user update this ticket? Only the user who raised it may (for now).
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->created_by;
    }
}
