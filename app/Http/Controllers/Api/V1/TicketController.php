<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    /**
     * GET /api/v1/tickets
     * Returns a paginated list (15 per page). Each ticket is transformed by TicketResource.
     */
    public function index(): AnonymousResourceCollection
    {
        return TicketResource::collection(Ticket::paginate());
    }

    /**
     * GET /api/v1/tickets/{ticket}
     * Returns one ticket. Route model binding answers 404 when it does not exist.
     */
    public function show(Ticket $ticket): TicketResource
    {
        return new TicketResource($ticket);
    }
}
