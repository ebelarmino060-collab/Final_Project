<?php

use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->viewer = User::factory()->create();
});

describe('index', function () {
    it('lists only users who raised a ticket', function () {
        $author = User::factory()->create();
        Ticket::factory()->recycle($author)->create();

        $this->withToken(jwtFor($this->viewer))
            ->getJson('/api/v1/authors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $author->id)
            ->assertJsonPath('data.0.type', 'user');
    });

    it('returns 401 when no token is provided', function () {
        $this->getJson('/api/v1/authors')->assertUnauthorized();
    });
});

describe('show', function () {
    it('returns the author with the timestamps that only author routes show', function () {
        $author = User::factory()->create();

        $this->withToken(jwtFor($this->viewer))
            ->getJson("/api/v1/authors/{$author->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.name', $author->name)
            ->assertJsonPath('data.attributes.email', $author->email)
            ->assertJsonStructure(['data' => ['attributes' => ['emailVerifiedAt', 'createdAt', 'updatedAt']]])
            ->assertJsonPath('data.links.self', route('authors.show', $author));
    });

    it('returns 404 for a user that does not exist', function () {
        $this->withToken(jwtFor($this->viewer))
            ->getJson('/api/v1/authors/9999')
            ->assertNotFound();
    });
});
