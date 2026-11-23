<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->token = jwtFor(User::factory()->create());
});

/**
 * GET /api/v1/tickets with the given query string as the logged-in user.
 */
function listTickets(string $query): TestResponse
{
    return test()->withToken(test()->token)->getJson('/api/v1/tickets?'.$query)->assertOk();
}

/**
 * The ticket ids of a list response, sorted so the order of the page does not matter.
 *
 * @return list<int>
 */
function ticketIds(TestResponse $response): array
{
    return collect($response->json('data'))->pluck('id')->sort()->values()->all();
}

it('filters by one status', function () {
    $open = Ticket::factory()->create(['status' => 'open']);
    Ticket::factory()->create(['status' => 'closed']);

    expect(ticketIds(listTickets('status=open')))->toBe([$open->id]);
});

it('filters by several statuses separated by commas', function () {
    $open = Ticket::factory()->create(['status' => 'open']);
    $pending = Ticket::factory()->create(['status' => 'pending']);
    Ticket::factory()->create(['status' => 'closed']);

    expect(ticketIds(listTickets('status=open,pending')))->toBe([$open->id, $pending->id]);
});

it('filters by priority', function () {
    $urgent = Ticket::factory()->create(['priority' => 'urgent']);
    Ticket::factory()->create(['priority' => 'low']);

    expect(ticketIds(listTickets('priority=urgent')))->toBe([$urgent->id]);
});

it('filters by title with a wildcard', function () {
    $printer = Ticket::factory()->create(['title' => 'Office printer jammed']);
    Ticket::factory()->create(['title' => 'VPN is down']);

    expect(ticketIds(listTickets('title=*printer*')))->toBe([$printer->id]);
});

it('filters by ticket number with a wildcard', function () {
    $match = Ticket::factory()->create(['ticket_number' => 'TCK-20260101-AAAAAA']);
    Ticket::factory()->create(['ticket_number' => 'TCK-20260202-BBBBBB']);

    expect(ticketIds(listTickets('ticketNumber=TCK-20260101-*')))->toBe([$match->id]);
});

it('searches the ticket number and the title', function () {
    $byNumber = Ticket::factory()->create(['ticket_number' => 'TCK-20260101-ZZZ999', 'title' => 'Email bounce']);
    $byTitle = Ticket::factory()->create(['title' => 'Laptop ZZZ999 will not boot']);
    Ticket::factory()->create(['title' => 'Unrelated']);

    expect(ticketIds(listTickets('search=ZZZ999')))->toBe([$byNumber->id, $byTitle->id]);
});

it('filters by the user who created the ticket', function () {
    $author = User::factory()->create();
    $mine = Ticket::factory()->recycle($author)->create();
    Ticket::factory()->create();

    expect(ticketIds(listTickets("createdBy={$author->id}")))->toBe([$mine->id]);
});

it('filters by the assigned user', function () {
    $assignee = User::factory()->create();
    $assigned = Ticket::factory()->assigned($assignee)->create();
    Ticket::factory()->assigned()->create();
    Ticket::factory()->create();

    expect(ticketIds(listTickets("assignedTo={$assignee->id}")))->toBe([$assigned->id]);
});

it('filters by a created date range', function () {
    $inRange = Ticket::factory()->create(['created_at' => '2026-03-15 10:00:00']);
    Ticket::factory()->create(['created_at' => '2026-05-01 10:00:00']);

    expect(ticketIds(listTickets('createdAt=2026-03-01,2026-03-31')))->toBe([$inRange->id]);
});

it('filters by a single created day', function () {
    $onDay = Ticket::factory()->create(['created_at' => '2026-03-15 10:00:00']);
    Ticket::factory()->create(['created_at' => '2026-03-16 10:00:00']);

    expect(ticketIds(listTickets('createdAt=2026-03-15')))->toBe([$onDay->id]);
});

it('combines filters', function () {
    $match = Ticket::factory()->create(['status' => 'open', 'priority' => 'high']);
    Ticket::factory()->create(['status' => 'open', 'priority' => 'low']);
    Ticket::factory()->create(['status' => 'closed', 'priority' => 'high']);

    expect(ticketIds(listTickets('status=open&priority=high')))->toBe([$match->id]);
});

it('includes the author through the filter and ignores relationships that are not allowed', function () {
    Ticket::factory()->create();

    listTickets('include=author,secrets')
        ->assertJsonStructure(['data' => [['includes' => ['author']]]]);
});

it('ignores unknown parameters and array values', function () {
    Ticket::factory(2)->create();

    expect(ticketIds(listTickets('unknown=1&apply=x&status[]=open')))->toHaveCount(2);
});

it('keeps the filters on the pagination links', function () {
    Ticket::factory(16)->create(['status' => 'open']);

    expect(listTickets('status=open')->json('links.next'))->toContain('status=open');
});
