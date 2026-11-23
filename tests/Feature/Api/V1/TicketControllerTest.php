<?php

use App\Models\Ticket;

it('lists every ticket under the v1 prefix', function () {
    Ticket::factory(3)->create();

    $this->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(3)
        ->assertJsonStructure([
            ['id', 'ticket_number', 'title', 'description', 'status', 'priority', 'created_by', 'assigned_to', 'created_at', 'updated_at'],
        ]);
});

it('returns 404 for the old unversioned tickets URL', function () {
    $this->getJson('/api/tickets')->assertNotFound();
});
