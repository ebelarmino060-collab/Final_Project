<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Filters\V1\TicketFilter;
use App\Http\Requests\Api\V1\ReplaceTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends APIController
{
    /**
     * The relationships a client may request with ?include=...
     *
     * @var list<string>
     */
    protected array $includable = ['author', 'assignee'];

    /**
     * GET /api/v1/tickets
     * Returns a paginated list (15 per page). Each ticket is transformed by TicketResource.
     */
    public function index(TicketFilter $filters): AnonymousResourceCollection
    {
        // Apply the filters from the URL (?status=open, ?search=printer, ?include=author ...)
        // withQueryString() keeps those filters on the pagination links
        return TicketResource::collection(
            Ticket::filter($filters)->paginate()->withQueryString()
        );
    }

    /**
     * GET /api/v1/tickets/{ticket}
     * Returns one ticket. Route model binding answers 404 when it does not exist.
     */
    public function show(Ticket $ticket): TicketResource
    {
        return new TicketResource($ticket->load($this->requestedIncludes()));
    }

    /**
     * POST /api/v1/tickets
     * Validates the body with StoreTicketRequest, then creates the ticket.
     * Laravel answers 201 Created automatically for a newly created resource.
     */
    public function store(StoreTicketRequest $request): TicketResource
    {
        // mappedAttributes() converts the nested JSON into database columns
        return new TicketResource(
            Ticket::create($request->mappedAttributes())
        );
    }

    /**
     * DELETE /api/v1/tickets/{ticket}
     * Deletes the ticket. A missing ticket never reaches this method: Laravel answers 404.
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $ticket->delete();

        return $this->ok('Ticket successfully deleted');
    }

    /**
     * PUT /api/v1/tickets/{ticket}
     * Replaces the whole ticket: every field must be sent (see ReplaceTicketRequest).
     */
    public function replace(ReplaceTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket->update($request->mappedAttributes());

        return new TicketResource($ticket);
    }

    /**
     * PATCH /api/v1/tickets/{ticket}
     * Updates only the fields that were sent (see UpdateTicketRequest): for example just the
     * status, just the priority, or just the assignee. Only if the policy allows it.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource|JsonResponse
    {
        // Ask TicketPolicy::update()
        if ($this->isAble('update', $ticket)) {
            // mappedAttributes() returns only the keys present in the request
            $ticket->update($request->mappedAttributes());

            return new TicketResource($ticket);
        }

        return $this->notAuthorized('You are not authorized to update that resource.');
    }

    /**
     * The allowed relationships the client asked for.
     *
     * @return list<string>
     */
    protected function requestedIncludes(): array
    {
        return array_values(array_filter($this->includable, fn (string $relationship): bool => $this->include($relationship)));
    }
}
