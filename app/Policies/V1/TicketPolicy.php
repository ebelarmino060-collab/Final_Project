<?php

namespace App\Policies\V1;

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

/**
 * Authorization rules for tickets. Each method returns true (allowed) or false (denied).
 * The rules read the abilities stored in the user's JWT.
 */
class TicketPolicy
{
    /**
     * May this user create a ticket? Needs the broad or the "own" create ability.
     */
    public function store(User $user): bool
    {
        return $user->tokenCan(Abilities::CreateTicket)
            || $user->tokenCan(Abilities::CreateOwnTicket);
    }

    /**
     * May this user update this ticket (PATCH)?
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Broad ability: any ticket
        if ($user->tokenCan(Abilities::UpdateTicket)) {
            return true;
        }

        // "Own" ability: only a ticket the user raised
        if ($user->tokenCan(Abilities::UpdateOwnTicket)) {
            return $user->id === $ticket->created_by;
        }

        return false;
    }

    /**
     * May this user replace this ticket (PUT)? Managers only.
     */
    public function replace(User $user, Ticket $ticket): bool
    {
        return $user->tokenCan(Abilities::ReplaceTicket);
    }

    /**
     * May this user delete this ticket?
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Broad ability: any ticket
        if ($user->tokenCan(Abilities::DeleteTicket)) {
            return true;
        }

        // "Own" ability: only a ticket the user raised
        if ($user->tokenCan(Abilities::DeleteOwnTicket)) {
            return $user->id === $ticket->created_by;
        }

        return false;
    }
}
