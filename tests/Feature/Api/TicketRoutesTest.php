<?php

use App\Models\Ticket;

it('lists every ticket from the database', function () {
    Ticket::factory(3)->create();

    $this->getJson('/api/tickets')
        ->assertOk()
        ->assertJsonCount(3)
        ->assertJsonStructure([
            ['id', 'ticket_number', 'title', 'description', 'status', 'priority', 'created_by', 'assigned_to', 'created_at', 'updated_at'],
        ]);
});
