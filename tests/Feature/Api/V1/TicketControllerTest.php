<?php

use App\Models\Ticket;
use App\Models\User;

it('lists every ticket under the v1 prefix', function () {
    Ticket::factory(3)->create();

    $this->withToken(jwtFor(User::factory()->create()))
        ->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(3)
        ->assertJsonStructure([
            ['id', 'ticket_number', 'title', 'description', 'status', 'priority', 'created_by', 'assigned_to', 'created_at', 'updated_at'],
        ]);
});

it('returns 401 when no token is provided', function () {
    $this->getJson('/api/v1/tickets')->assertUnauthorized();
});

it('returns 404 for the old unversioned tickets URL', function () {
    $this->getJson('/api/tickets')->assertNotFound();
});
