<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Filters\V1\TicketFilter;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tickets raised by one user: /api/v1/users/{user}/tickets
 */
class UserTicketsController extends APIController
{
    /**
     * GET /api/v1/users/{user}/tickets
     * Lists only this user's tickets. All TicketFilter parameters work here too.
     */
    public function index(User $user, TicketFilter $filters): AnonymousResourceCollection
    {
        return TicketResource::collection(
            Ticket::where('created_by', $user->id)->filter($filters)->paginate()->withQueryString()
        );
    }
}
