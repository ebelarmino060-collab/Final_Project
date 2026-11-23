<?php

use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('index', function () {
    it('returns a page of 15 tickets in the resource structure with pagination links and meta', function () {
        Ticket::factory(20)->create();

        $this->withToken(jwtFor($this->user))
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonStructure([
                'data' => [[
                    'type',
                    'id',
                    'attributes' => ['ticketNumber', 'title', 'description', 'status', 'priority', 'createdAt', 'updatedAt'],
                    'relationships' => ['author' => ['data' => ['type', 'id']], 'assignee'],
                    'links' => ['self'],
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('returns the remaining tickets on page 2', function () {
        Ticket::factory(20)->create();

        $this->withToken(jwtFor($this->user))
            ->getJson('/api/v1/tickets?page=2')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    });

    it('returns 401 when no token is provided', function () {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
    });

    it('returns 404 for the old unversioned tickets URL', function () {
        $this->getJson('/api/tickets')->assertNotFound();
    });
});

describe('show', function () {
    it('returns one ticket with its author and assignee relationships', function () {
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->assigned($assignee)->create(['status' => 'pending', 'priority' => 'high']);

        $this->withToken(jwtFor($this->user))
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.type', 'ticket')
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('data.attributes.ticketNumber', $ticket->ticket_number)
            ->assertJsonPath('data.attributes.title', $ticket->title)
            ->assertJsonPath('data.attributes.status', 'pending')
            ->assertJsonPath('data.attributes.priority', 'high')
            ->assertJsonPath('data.relationships.author.data.id', $ticket->created_by)
            ->assertJsonPath('data.relationships.assignee.data.id', $assignee->id)
            ->assertJsonPath('data.links.self', route('tickets.show', $ticket));
    });

    it('returns a null assignee for an unassigned ticket', function () {
        $ticket = Ticket::factory()->create();

        $this->withToken(jwtFor($this->user))
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.relationships.assignee.data', null);
    });

    it('returns 404 for a ticket that does not exist', function () {
        $this->withToken(jwtFor($this->user))
            ->getJson('/api/v1/tickets/9999')
            ->assertNotFound();
    });
});
