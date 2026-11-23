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
