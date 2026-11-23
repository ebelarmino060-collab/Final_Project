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
 * @group User Tickets
 *
 * The tickets raised by one user: /api/v1/users/{user}/tickets. A ticket that does not
 * belong to the user in the URL returns 404. The same permissions apply as on /tickets.
 */
class UserTicketsController extends APIController
{
    /**
     * List a user's tickets
     *
     * Lists only the tickets this user raised. Every ticket filter and sort works here too.
     *
     * @urlParam user integer required The user id. Example: 1
     *
     * @queryParam status string Filter by status. Use a comma for several. Example: open
     * @queryParam priority string Filter by priority. Use a comma for several. Example: urgent
     * @queryParam search string Search the ticket number and the title. Example: printer
     * @queryParam sort string Sort by ticketNumber, title, status, priority, createdAt or updatedAt. Example: -createdAt
     * @queryParam include string Include related users: author, assignee. Example: assignee
     *
     * @apiResourceCollection App\Http\Resources\V1\TicketResource
     *
     * @apiResourceModel App\Models\Ticket paginate=15
     */
    public function index(User $user, TicketFilter $filters): AnonymousResourceCollection
    {
        return TicketResource::collection(
            Ticket::where('created_by', $user->id)->filter($filters)->paginate()->withQueryString()
        );
    }

    /**
     * Create a ticket for a user
     *
     * Creates a ticket raised by the user in the URL. Regular users can only use their own id.
     *
     * @urlParam user integer required The user id. Example: 1
     *
     * @apiResource App\Http\Resources\V1\TicketResource status=201
     *
     * @apiResourceModel App\Models\Ticket
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
     * Replace a user's ticket
     *
     * Managers only.
     *
     * @urlParam user integer required The user id. Example: 1
     * @urlParam ticket integer required The ticket id. Example: 1
     *
     * @apiResource App\Http\Resources\V1\TicketResource
     *
     * @apiResourceModel App\Models\Ticket
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
     * Update a user's ticket
     *
     * Changes only the fields that are sent.
     *
     * @urlParam user integer required The user id. Example: 1
     * @urlParam ticket integer required The ticket id. Example: 1
     *
     * @apiResource App\Http\Resources\V1\TicketResource
     *
     * @apiResourceModel App\Models\Ticket
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
     * Delete a user's ticket
     *
     * @urlParam user integer required The user id. Example: 1
     * @urlParam ticket integer required The ticket id. Example: 1
     *
     * @response 200 {"data":null,"message":"Ticket successfully deleted","status":200}
     * @response 404 scenario="Ticket belongs to another user" {"errors":[{"status":404,"message":"The resource cannot be found.","source":"App\\Models\\Ticket"}]}
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
