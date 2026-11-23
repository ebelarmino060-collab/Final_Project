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
                    'attributes' => ['ticketNumber', 'title', 'status', 'priority', 'createdAt', 'updatedAt'],
                    'relationships' => ['author' => ['data' => ['type', 'id'], 'links' => ['self']], 'assignee'],
                    'links' => ['self'],
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('does not include the description on the list', function () {
        Ticket::factory()->create();

        $this->withToken(jwtFor($this->user))
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonMissingPath('data.0.attributes.description');
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
            ->assertJsonPath('data.attributes.description', $ticket->description)
            ->assertJsonPath('data.attributes.status', 'pending')
            ->assertJsonPath('data.attributes.priority', 'high')
            ->assertJsonPath('data.relationships.author.data.id', $ticket->created_by)
            ->assertJsonPath('data.relationships.author.links.self', route('authors.show', $ticket->created_by))
            ->assertJsonPath('data.relationships.assignee.data.id', $assignee->id)
            ->assertJsonPath('data.relationships.assignee.links.self', route('authors.show', $assignee))
            ->assertJsonPath('data.links.self', route('tickets.show', $ticket));
    });

    it('returns a null assignee for an unassigned ticket', function () {
        $ticket = Ticket::factory()->create();

        $this->withToken(jwtFor($this->user))
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.relationships.assignee.data', null)
            ->assertJsonMissingPath('data.relationships.assignee.links');
    });

    it('returns 404 for a ticket that does not exist', function () {
        $this->withToken(jwtFor($this->user))
            ->getJson('/api/v1/tickets/9999')
            ->assertNotFound();
    });
});

describe('include', function () {
    it('omits the includes key when nothing is requested', function () {
        $ticket = Ticket::factory()->create();

        $this->withToken(jwtFor($this->user))
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.includes');
    });

    it('includes the author on the list when requested', function () {
        $author = User::factory()->create();
        Ticket::factory()->recycle($author)->create();

        $this->withToken(jwtFor($this->user))
            ->getJson('/api/v1/tickets?include=author')
            ->assertOk()
            ->assertJsonPath('data.0.includes.author.id', $author->id)
            ->assertJsonPath('data.0.includes.author.attributes.name', $author->name)
            ->assertJsonMissingPath('data.0.includes.assignee');
    });

    it('includes the author and the assignee of one ticket when both are requested', function () {
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->assigned($assignee)->create();

        $this->withToken(jwtFor($this->user))
            ->getJson("/api/v1/tickets/{$ticket->id}?include=author,assignee")
            ->assertOk()
            ->assertJsonPath('data.includes.author.id', $ticket->created_by)
            ->assertJsonPath('data.includes.assignee.id', $assignee->id);
    });

    it('eager loads the authors of the whole page in one query', function () {
        Ticket::factory(5)->create();
        $token = jwtFor($this->user);

        DB::enableQueryLog();
        $this->withToken($token)->getJson('/api/v1/tickets?include=author')->assertOk();

        $userQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'from `users`') && str_contains($query['query'], ' in ('));

        expect($userQueries)->toHaveCount(1);
    });
});

describe('store', function () {
    it('creates a ticket and returns 201 with a generated ticket number', function () {
        $assignee = User::factory()->create();

        $response = $this->withToken(jwtFor($this->user))
            ->postJson('/api/v1/tickets', ticketPayload(
                ['title' => 'VPN down', 'priority' => 'urgent'],
                ['author' => $this->user->id, 'assignee' => $assignee->id],
            ))
            ->assertCreated()
            ->assertJsonPath('data.attributes.title', 'VPN down')
            ->assertJsonPath('data.attributes.priority', 'urgent')
            ->assertJsonPath('data.relationships.author.data.id', $this->user->id)
            ->assertJsonPath('data.relationships.assignee.data.id', $assignee->id);

        $ticket = Ticket::findOrFail($response->json('data.id'));
        expect($ticket)
            ->title->toBe('VPN down')
            ->created_by->toBe($this->user->id)
            ->assigned_to->toBe($assignee->id)
            ->ticket_number->toMatch('/^TCK-\d{8}-[A-Z0-9]{6}$/');
    });

    it('defaults a new ticket to open with medium priority', function () {
        $this->withToken(jwtFor($this->user))
            ->postJson('/api/v1/tickets', ticketPayload(['status' => null, 'priority' => null], ['author' => $this->user->id]))
            ->assertCreated()
            ->assertJsonPath('data.attributes.status', 'open')
            ->assertJsonPath('data.attributes.priority', 'medium');
    });

    it('ignores a ticket number sent by the client', function () {
        $this->withToken(jwtFor($this->user))
            ->postJson('/api/v1/tickets', ticketPayload(['ticketNumber' => 'TCK-HACKED'], ['author' => $this->user->id]))
            ->assertCreated();

        expect(Ticket::sole()->ticket_number)->not->toBe('TCK-HACKED');
    });

    it('returns 422 when required fields are missing', function () {
        $this->withToken(jwtFor($this->user))
            ->postJson('/api/v1/tickets', ['data' => ['attributes' => ['status' => 'open']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data.attributes.title' => 'The data.attributes.title field is required.',
                'data.attributes.description' => 'The data.attributes.description field is required.',
                'data.relationships.author.data.id' => 'The data.relationships.author.data.id field is required.',
            ]);

        expect(Ticket::count())->toBe(0);
    });

    it('returns 422 with the custom message for an invalid status or priority', function () {
        $this->withToken(jwtFor($this->user))
            ->postJson('/api/v1/tickets', ticketPayload(['status' => 'done', 'priority' => 'asap'], ['author' => $this->user->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data.attributes.status' => 'The data.attributes.status value is invalid. Please use open, in_progress, pending, resolved, closed.',
                'data.attributes.priority' => 'The data.attributes.priority value is invalid. Please use low, medium, high, urgent.',
            ]);
    });

    it('returns 422 for an author or assignee that does not exist', function () {
        $this->withToken(jwtFor($this->user))
            ->postJson('/api/v1/tickets', ticketPayload([], ['author' => 99999, 'assignee' => 99998]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data.relationships.author.data.id' => 'The selected data.relationships.author.data.id is invalid.',
                'data.relationships.assignee.data.id' => 'The selected data.relationships.assignee.data.id is invalid.',
            ]);
    });

    it('returns 401 when no token is provided', function () {
        $this->postJson('/api/v1/tickets', ticketPayload())->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('deletes the ticket', function () {
        $ticket = Ticket::factory()->create();

        $this->withToken(jwtFor($this->user))
            ->deleteJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => 'Ticket successfully deleted', 'status' => 200]);

        $this->assertModelMissing($ticket);
    });

    it('returns 404 when the ticket is already gone', function () {
        $ticket = Ticket::factory()->create();
        $token = jwtFor($this->user);

        $this->withToken($token)->deleteJson("/api/v1/tickets/{$ticket->id}")->assertOk();
        $this->withToken($token)->deleteJson("/api/v1/tickets/{$ticket->id}")->assertNotFound();
        $this->withToken($token)->getJson("/api/v1/tickets/{$ticket->id}")->assertNotFound();
    });

    it('returns 401 when no token is provided', function () {
        $ticket = Ticket::factory()->create();

        $this->deleteJson("/api/v1/tickets/{$ticket->id}")->assertUnauthorized();
        $this->assertModelExists($ticket);
    });
});

describe('replace', function () {
    it('replaces every field of the ticket', function () {
        $ticket = Ticket::factory()->assigned()->create(['ticket_number' => 'TCK-20260101-KEEP01']);
        $newAuthor = User::factory()->create();

        $this->withToken(jwtFor($this->user))
            ->putJson("/api/v1/tickets/{$ticket->id}", ticketPayload(
                ['title' => 'Replaced', 'description' => 'Replaced via PUT.', 'status' => 'resolved', 'priority' => 'low'],
                ['author' => $newAuthor->id, 'assignee' => null],
            ))
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'Replaced')
            ->assertJsonPath('data.attributes.status', 'resolved');

        expect($ticket->fresh())
            ->title->toBe('Replaced')
            ->description->toBe('Replaced via PUT.')
            ->priority->value->toBe('low')
            ->created_by->toBe($newAuthor->id)
            ->assigned_to->toBeNull()
            ->ticket_number->toBe('TCK-20260101-KEEP01');
    });

    it('returns 422 and changes nothing when fields are missing', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $this->withToken(jwtFor($this->user))
            ->putJson("/api/v1/tickets/{$ticket->id}", ['data' => ['attributes' => ['status' => 'closed']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data.attributes.title' => 'The data.attributes.title field is required.',
                'data.attributes.description' => 'The data.attributes.description field is required.',
                'data.attributes.priority' => 'The data.attributes.priority field is required.',
                'data.relationships.author.data.id' => 'The data.relationships.author.data.id field is required.',
                'data.relationships.assignee' => 'The data.relationships.assignee field is required.',
            ]);

        expect($ticket->fresh()->status->value)->toBe('open');
    });

    it('returns 404 for a ticket that does not exist', function () {
        $this->withToken(jwtFor($this->user))
            ->putJson('/api/v1/tickets/9999', ticketPayload([], ['author' => $this->user->id, 'assignee' => null]))
            ->assertNotFound();
    });
});
