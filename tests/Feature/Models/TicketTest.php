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

it('links a ticket to its author and assignee', function () {
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->recycle($author)->assigned($assignee)->create();

    expect($ticket->author->is($author))->toBeTrue()
        ->and($ticket->assignee->is($assignee))->toBeTrue()
        ->and($author->tickets->modelKeys())->toBe([$ticket->id])
        ->and($assignee->assignedTickets->modelKeys())->toBe([$ticket->id])
        ->and($assignee->tickets)->toBeEmpty();
});
