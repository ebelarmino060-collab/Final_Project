<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

it('generates a ticket number when none is given', function () {
    $ticket = Ticket::create([
        'title' => 'Printer offline',
        'description' => 'The second floor printer is offline.',
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::High,
        'created_by' => User::factory()->create()->id,
    ]);

    expect($ticket->ticket_number)->toMatch('/^TCK-\d{8}-[A-Z0-9]{6}$/');
});

it('keeps a ticket number that was already set', function () {
    $ticket = Ticket::factory()->create(['ticket_number' => 'TCK-20260101-ABC123']);

    expect($ticket->fresh()->ticket_number)->toBe('TCK-20260101-ABC123');
});

it('casts status and priority to their enums', function () {
    $ticket = Ticket::factory()->create([
        'status' => 'in_progress',
        'priority' => 'urgent',
    ]);

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::InProgress)
        ->priority->toBe(TicketPriority::Urgent);
});
