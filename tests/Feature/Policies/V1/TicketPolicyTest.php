<?php

use App\Models\Ticket;
use App\Models\User;
use App\Policies\V1\TicketPolicy;

/**
 * Make $user the owner of the current request's JWT, so tokenCan() reads their abilities.
 */
function actAsTokenOwner(User $user): void
{
    auth('api')->setToken(jwtFor($user));
}

/**
 * The policy's answer for one ability, as the given kind of user, on an own or a foreign ticket.
 */
function ticketPolicyAllows(string $ability, bool $isManager, bool $ownsTicket): bool
{
    $user = User::factory()->create(['is_manager' => $isManager]);
    $ticket = $ownsTicket ? Ticket::factory()->recycle($user)->create() : Ticket::factory()->create();

    actAsTokenOwner($user);

    return $ability === 'store'
        ? (new TicketPolicy)->store($user)
        : (new TicketPolicy)->{$ability}($user, $ticket);
}

test('the ticket permission matrix', function (string $ability, bool $isManager, bool $ownsTicket, bool $allowed) {
    expect(ticketPolicyAllows($ability, $isManager, $ownsTicket))->toBe($allowed);
})->with([
    'regular user creates' => ['store', false, true, true],
    'manager creates' => ['store', true, true, true],
    'regular user updates own' => ['update', false, true, true],
    'regular user updates other' => ['update', false, false, false],
    'manager updates other' => ['update', true, false, true],
    'regular user replaces own' => ['replace', false, true, false],
    'manager replaces other' => ['replace', true, false, true],
    'regular user deletes own' => ['delete', false, true, true],
    'regular user deletes other' => ['delete', false, false, false],
    'manager deletes other' => ['delete', true, false, true],
]);

test('a user without a token has no abilities', function () {
    app('auth')->forgetGuards();
    $user = User::factory()->manager()->create();

    expect((new TicketPolicy)->store($user))->toBeFalse()
        ->and((new TicketPolicy)->update($user, Ticket::factory()->recycle($user)->create()))->toBeFalse();
});
