<?php

use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->token = jwtFor(User::factory()->create());
});

describe('index', function () {
    it('lists only the tickets the user raised, without descriptions', function () {
        $mine = Ticket::factory(2)->recycle($this->author)->create();
        Ticket::factory()->create();

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/users/{$this->author->id}/tickets")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('data.0.attributes.description');

        expect(collect($response->json('data'))->pluck('id')->sort()->values()->all())
            ->toBe($mine->modelKeys());
    });

    it('applies the ticket filters to the nested list', function () {
        $open = Ticket::factory()->recycle($this->author)->create(['status' => 'open']);
        Ticket::factory()->recycle($this->author)->create(['status' => 'closed']);
        Ticket::factory()->create(['status' => 'open']);

        $this->withToken($this->token)
            ->getJson("/api/v1/users/{$this->author->id}/tickets?status=open")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $open->id);
    });

    it('returns 404 for a user that does not exist', function () {
        $this->withToken($this->token)
            ->getJson('/api/v1/users/9999/tickets')
            ->assertNotFound();
    });

    it('returns 401 when no token is provided', function () {
        $this->getJson("/api/v1/users/{$this->author->id}/tickets")->assertUnauthorized();
    });
});

describe('store', function () {
    it('creates a ticket for the user in the URL without an author in the body', function () {
        $response = $this->withToken(jwtFor($this->author))
            ->postJson("/api/v1/users/{$this->author->id}/tickets", ticketPayload())
            ->assertCreated()
            ->assertJsonPath('data.relationships.author.data.id', $this->author->id);

        expect(Ticket::findOrFail($response->json('data.id'))->created_by)->toBe($this->author->id);
    });

    it('returns 422 when a regular user creates a ticket under someone else', function () {
        $other = User::factory()->create();

        $this->withToken(jwtFor($this->author))
            ->postJson("/api/v1/users/{$other->id}/tickets", ticketPayload())
            ->assertUnprocessable()
            ->assertApiValidationErrors(['author' => "The author field must be {$this->author->id}."]);

        expect(Ticket::count())->toBe(0);
    });

    it('lets a manager create a ticket under any user', function () {
        $this->withToken(jwtFor(User::factory()->manager()->create()))
            ->postJson("/api/v1/users/{$this->author->id}/tickets", ticketPayload())
            ->assertCreated()
            ->assertJsonPath('data.relationships.author.data.id', $this->author->id);
    });
});

describe('replace', function () {
    it('returns 403 when a regular user replaces their own ticket', function () {
        $ticket = Ticket::factory()->recycle($this->author)->create();

        $this->withToken(jwtFor($this->author))
            ->putJson("/api/v1/users/{$this->author->id}/tickets/{$ticket->id}", ticketPayload([], ['author' => $this->author->id, 'assignee' => null]))
            ->assertForbidden();
    });

    it('lets a manager replace the ticket', function () {
        $ticket = Ticket::factory()->recycle($this->author)->create();

        $this->withToken(jwtFor(User::factory()->manager()->create()))
            ->putJson("/api/v1/users/{$this->author->id}/tickets/{$ticket->id}", ticketPayload(['title' => 'Renamed'], ['author' => $this->author->id, 'assignee' => null]))
            ->assertOk();

        expect($ticket->fresh()->title)->toBe('Renamed');
    });
});

describe('update and destroy', function () {
    it('lets the author update and then delete their own ticket', function () {
        $ticket = Ticket::factory()->recycle($this->author)->create(['status' => 'open']);
        $token = jwtFor($this->author);

        $this->withToken($token)
            ->patchJson("/api/v1/users/{$this->author->id}/tickets/{$ticket->id}", ['data' => ['attributes' => ['status' => 'pending']]])
            ->assertOk()
            ->assertJsonPath('data.attributes.status', 'pending');

        $this->withToken($token)
            ->deleteJson("/api/v1/users/{$this->author->id}/tickets/{$ticket->id}")
            ->assertOk();

        $this->assertModelMissing($ticket);
    });

    it('returns 404 when the ticket does not belong to the user in the URL', function () {
        $foreign = Ticket::factory()->create();
        $token = jwtFor($this->author);

        $this->withToken($token)
            ->deleteJson("/api/v1/users/{$this->author->id}/tickets/{$foreign->id}")
            ->assertNotFound();
        $this->withToken($token)
            ->patchJson("/api/v1/users/{$this->author->id}/tickets/{$foreign->id}", ['data' => ['attributes' => ['status' => 'closed']]])
            ->assertNotFound();
        $this->withToken($token)
            ->putJson("/api/v1/users/{$this->author->id}/tickets/{$foreign->id}", ticketPayload([], ['author' => $this->author->id, 'assignee' => null]))
            ->assertNotFound();

        $this->assertModelExists($foreign);
    });

    it('returns 403 when a user deletes another user ticket through that user URL', function () {
        $ticket = Ticket::factory()->recycle($this->author)->create();

        $this->withToken(jwtFor(User::factory()->create()))
            ->deleteJson("/api/v1/users/{$this->author->id}/tickets/{$ticket->id}")
            ->assertForbidden();

        $this->assertModelExists($ticket);
    });
});
