<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
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
    public function index(): AnonymousResourceCollection
    {
        // ?include=author,assignee: eager load them in one extra query each (no N+1)
        return TicketResource::collection(
            Ticket::with($this->requestedIncludes())->paginate()
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
     * The allowed relationships the client asked for.
     *
     * @return list<string>
     */
    protected function requestedIncludes(): array
    {
        return array_values(array_filter($this->includable, fn (string $relationship): bool => $this->include($relationship)));
    }
}
