<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('register', function () {
    it('creates the user with a hashed password and returns a bearer token', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Registered successfully')
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.expires_in', config('jwt.ttl') * 60)
            ->assertJsonStructure(['data' => ['access_token']]);

        $user = User::where('email', 'test@example.com')->sole();
        expect(Hash::check('password', $user->password))->toBeTrue();
    });

    it('rejects a duplicate email and an unconfirmed password with 422', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()
            ->assertApiValidationErrors([
                'email' => 'The email has already been taken.',
                'password' => 'The password field confirmation does not match.',
            ]);
    });
});

describe('login', function () {
    it('returns a token for valid credentials', function () {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);
    });

    it('returns 401 for a wrong password', function () {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertExactJson(['message' => 'Invalid credentials', 'status' => 401]);
    });

    it('returns 422 when the email is missing', function () {
        $this->postJson('/api/v1/auth/login', ['password' => 'password'])
            ->assertUnprocessable()
            ->assertApiValidationErrors(['email' => 'The email field is required.']);
    });
});

describe('me', function () {
    it('returns the user that owns the token', function () {
        $user = User::factory()->create();

        $this->withToken(jwtFor($user))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.password');
    });

    it('returns 401 without a token', function () {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    });
});

describe('refresh and logout', function () {
    it('refreshes the token and blacklists the old one', function () {
        $oldToken = jwtFor(User::factory()->create());

        $newToken = $this->withToken($oldToken)
            ->postJson('/api/v1/auth/refresh')
            ->assertOk()
            ->json('data.access_token');

        expect($newToken)->not->toBe($oldToken);

        app('auth')->forgetGuards();
        $this->withToken($oldToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
    });

    it('revokes the token on logout', function () {
        $token = jwtFor(User::factory()->create());

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Successfully logged out');

        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    });
});
