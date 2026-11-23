<?php

use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->token = jwtFor(User::factory()->create(['name' => 'Viewer']));
});

/**
 * Create a user who has raised one ticket, so they appear on /authors.
 */
function authorNamed(string $name, string $email): User
{
    $author = User::factory()->create(['name' => $name, 'email' => $email]);
    Ticket::factory()->recycle($author)->create();

    return $author;
}

it('filters authors by name with a wildcard and sorts them by name', function () {
    $bob = authorNamed('Bob Stone', 'bob@example.com');
    $abby = authorNamed('Abby Stone', 'abby@example.com');
    authorNamed('Carl Day', 'carl@example.com');

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/authors?name=*Stone*&sort=name')
        ->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$abby->id, $bob->id]);
});

it('filters authors by email and by id', function () {
    $match = authorNamed('Ann', 'ann@acme.test');
    authorNamed('Ben', 'ben@other.test');

    $this->withToken($this->token)
        ->getJson('/api/v1/authors?email=*@acme.test')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);

    $this->withToken($this->token)
        ->getJson("/api/v1/authors?id={$match->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('sorts authors newest first', function () {
    $old = authorNamed('Old', 'old@example.com');
    $old->forceFill(['created_at' => '2026-01-01 00:00:00'])->save();
    $new = authorNamed('New', 'new@example.com');

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/authors?sort=-createdAt')
        ->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$new->id, $old->id]);
});

it('includes the tickets of each author through the filter', function () {
    authorNamed('Ann', 'ann@example.com');

    $this->withToken($this->token)
        ->getJson('/api/v1/authors?include=tickets')
        ->assertOk()
        ->assertJsonCount(1, 'data.0.includes');
});
