<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->manager = User::factory()->manager()->create();
    $this->regular = User::factory()->create();
});

/**
 * Build a JSON:API user request body.
 *
 * @param  array<string, mixed>  $attributes
 * @return array{data: array{attributes: array<string, mixed>}}
 */
function userPayload(array $attributes = []): array
{
    return ['data' => ['attributes' => array_merge([
        'name' => 'New Staff',
        'email' => 'staff@example.com',
        'isManager' => false,
        'password' => 'password123',
    ], $attributes)]];
}

describe('index and show', function () {
    it('lists every user with the filters applied', function () {
        User::factory()->create(['name' => 'Zed Filtered']);

        $this->withToken(jwtFor($this->regular))
            ->getJson('/api/v1/users?name=*Filtered*')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.name', 'Zed Filtered');
    });

    it('shows a user with isManager and the tickets they raised when included', function () {
        Ticket::factory()->recycle($this->manager)->create();

        $this->withToken(jwtFor($this->regular))
            ->getJson("/api/v1/users/{$this->manager->id}?include=tickets")
            ->assertOk()
            ->assertJsonPath('data.attributes.isManager', true)
            ->assertJsonCount(1, 'data.includes');
    });
});

describe('store', function () {
    it('lets a manager create a user with a hashed password', function () {
        $response = $this->withToken(jwtFor($this->manager))
            ->postJson('/api/v1/users', userPayload(['isManager' => true]))
            ->assertCreated()
            ->assertJsonPath('data.attributes.email', 'staff@example.com')
            ->assertJsonPath('data.attributes.isManager', true);

        $user = User::findOrFail($response->json('data.id'));
        expect($user->is_manager)->toBeTrue()
            ->and(Hash::check('password123', $user->password))->toBeTrue();
    });

    it('returns 403 and creates nothing when a regular user creates a user', function () {
        $this->withToken(jwtFor($this->regular))
            ->postJson('/api/v1/users', userPayload(['email' => 'sneaky@example.com', 'isManager' => true]))
            ->assertForbidden()
            ->assertJsonPath('errors.0.message', 'You are not authorized to create that resource.');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    });

    it('returns 422 for a taken email and a short password', function () {
        $this->withToken(jwtFor($this->manager))
            ->postJson('/api/v1/users', userPayload(['email' => $this->regular->email, 'password' => 'short']))
            ->assertUnprocessable()
            ->assertApiValidationErrors([
                'data.attributes.email' => 'The data.attributes.email has already been taken.',
                'data.attributes.password' => 'The data.attributes.password field must be at least 8 characters.',
            ]);
    });
});

describe('replace and update', function () {
    it('lets a manager replace a user while keeping the same email', function () {
        $this->withToken(jwtFor($this->manager))
            ->putJson("/api/v1/users/{$this->regular->id}", userPayload(['name' => 'Replaced', 'email' => $this->regular->email]))
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Replaced');
    });

    it('returns 422 when a replace is missing fields', function () {
        $this->withToken(jwtFor($this->manager))
            ->putJson("/api/v1/users/{$this->regular->id}", ['data' => ['attributes' => ['name' => 'Only name']]])
            ->assertUnprocessable()
            ->assertApiValidationErrors(['data.attributes.email', 'data.attributes.isManager', 'data.attributes.password']);
    });

    it('lets a manager promote a user with a partial update', function () {
        $this->withToken(jwtFor($this->manager))
            ->patchJson("/api/v1/users/{$this->regular->id}", ['data' => ['attributes' => ['isManager' => true]]])
            ->assertOk()
            ->assertJsonPath('data.attributes.isManager', true);

        expect($this->regular->fresh()->is_manager)->toBeTrue();
    });

    it('returns 403 when a regular user promotes themselves', function () {
        $this->withToken(jwtFor($this->regular))
            ->patchJson("/api/v1/users/{$this->regular->id}", ['data' => ['attributes' => ['isManager' => true]]])
            ->assertForbidden();

        expect($this->regular->fresh()->is_manager)->toBeFalse();
    });
});

describe('destroy', function () {
    it('lets a manager delete a user', function () {
        $this->withToken(jwtFor($this->manager))
            ->deleteJson("/api/v1/users/{$this->regular->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User successfully deleted');

        $this->assertModelMissing($this->regular);
    });

    it('returns 403 when a regular user deletes a user', function () {
        $this->withToken(jwtFor($this->regular))
            ->deleteJson("/api/v1/users/{$this->manager->id}")
            ->assertForbidden();

        $this->assertModelExists($this->manager);
    });
});
