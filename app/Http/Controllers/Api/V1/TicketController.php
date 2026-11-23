<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

class TicketController extends Controller
{
    /**
     * GET /api/v1/tickets
     * Returns every ticket (pagination and filters come in later lessons).
     *
     * @return Collection<int, Ticket>
     */
    public function index(): Collection
    {
        return Ticket::all();
    }
}
