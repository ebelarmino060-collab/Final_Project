<?php

namespace App\Http\Resources\V1;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a Ticket model into the JSON structure the API returns.
 *
 * @mixin Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * Build the array that becomes the JSON response.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // What kind of object this is
            'type' => 'ticket',
            'id' => $this->id,
            // The ticket's own fields, renamed to camelCase for the client
            'attributes' => [
                'ticketNumber' => $this->ticket_number,
                'title' => $this->title,
                // Shown on the detail page only: hidden on the list endpoints
                'description' => $this->when(
                    ! $request->routeIs(['tickets.index', 'users.tickets.index']),
                    $this->description
                ),
                'status' => $this->status,
                'priority' => $this->priority,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],
            // Related objects: the ids of the author and the assignee
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'user',
                        'id' => $this->created_by,
                    ],
                    // Link to the author's own endpoint
                    'links' => [
                        'self' => route('authors.show', ['author' => $this->created_by]),
                    ],
                ],
                'assignee' => [
                    'data' => $this->assigned_to === null ? null : [
                        'type' => 'user',
                        'id' => $this->assigned_to,
                    ],
                    // Link to the assignee's user endpoint, only when the ticket is assigned
                    $this->mergeWhen($this->assigned_to !== null, fn (): array => [
                        'links' => [
                            'self' => route('authors.show', ['author' => $this->assigned_to]),
                        ],
                    ]),
                ],
            ],
            // Link to this ticket
            'links' => [
                'self' => route('tickets.show', ['ticket' => $this->id]),
            ],
        ];
    }
}
