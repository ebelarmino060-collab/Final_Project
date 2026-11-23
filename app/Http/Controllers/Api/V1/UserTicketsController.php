<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Filters\V1\TicketFilter;
use App\Http\Requests\Api\V1\ReplaceTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

    /**
     * POST /api/v1/users/{user}/tickets
     * Creates a ticket raised by the user named in the URL.
     */
    public function store(StoreTicketRequest $request, User $user): TicketResource|JsonResponse
    {
        if ($this->isAble('store', Ticket::class)) {
            // ['author' => 'created_by'] turns the id taken from the URL into the created_by column
            return new TicketResource(
                Ticket::create($request->mappedAttributes(['author' => 'created_by']))
            );
        }

        return $this->notAuthorized('You are not authorized to create that resource.');
    }

    /**
     * PUT /api/v1/users/{user}/tickets/{ticket}
     * Replaces the whole ticket (managers only).
     */
    public function replace(ReplaceTicketRequest $request, User $user, Ticket $ticket): TicketResource|JsonResponse
    {
        if ($this->isAble('replace', $ticket)) {
            $ticket->update($request->mappedAttributes());

            return new TicketResource($ticket);
        }

        return $this->notAuthorized('You are not authorized to replace that resource.');
    }

    /**
     * PATCH /api/v1/users/{user}/tickets/{ticket}
     * Updates only the fields that were sent.
     */
    public function update(UpdateTicketRequest $request, User $user, Ticket $ticket): TicketResource|JsonResponse
    {
        if ($this->isAble('update', $ticket)) {
            $ticket->update($request->mappedAttributes());

            return new TicketResource($ticket);
        }

        return $this->notAuthorized('You are not authorized to update that resource.');
    }

    /**
     * DELETE /api/v1/users/{user}/tickets/{ticket}
     * Deletes the ticket. The scoped binding returns 404 if it is not this user's ticket.
     */
    public function destroy(User $user, Ticket $ticket): JsonResponse
    {
        if ($this->isAble('delete', $ticket)) {
            $ticket->delete();

            return $this->ok('Ticket successfully deleted');
        }

        return $this->notAuthorized('You are not authorized to delete that resource.');
    }
}
