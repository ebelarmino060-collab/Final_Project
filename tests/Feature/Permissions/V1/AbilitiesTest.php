<?php

use App\Models\User;
use App\Permissions\V1\Abilities;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

test('a manager receives every ticket and user ability', function () {
    expect(Abilities::getAbilities(User::factory()->manager()->make()))->toBe([
        'ticket:create', 'ticket:update', 'ticket:replace', 'ticket:delete',
        'user:create', 'user:update', 'user:replace', 'user:delete',
    ]);
});

test('a regular user receives only the own-ticket abilities', function () {
    expect(Abilities::getAbilities(User::factory()->make()))->toBe([
        'ticket:own:create', 'ticket:own:update', 'ticket:own:delete',
    ]);
});

it('writes the abilities into the token issued at login', function () {
    $manager = User::factory()->manager()->create();

    $token = $this->postJson('/api/v1/auth/login', ['email' => $manager->email, 'password' => 'password'])
        ->assertOk()
        ->json('data.access_token');

    expect(JWTAuth::setToken($token)->getPayload()->get('abilities'))->toContain('ticket:replace', 'user:delete');
});
