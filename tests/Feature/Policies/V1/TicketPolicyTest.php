<?php

use App\Models\Ticket;
use App\Models\User;
use App\Policies\V1\TicketPolicy;

test('the user who raised a ticket may update it', function () {
    $author = User::factory()->create();
    $ticket = Ticket::factory()->recycle($author)->create();

    expect((new TicketPolicy)->update($author, $ticket))->toBeTrue();
});

test('another user may not update the ticket', function () {
    $ticket = Ticket::factory()->create();

    expect((new TicketPolicy)->update(User::factory()->create(), $ticket))->toBeFalse();
});
