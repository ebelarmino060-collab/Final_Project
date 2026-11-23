<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginUserRequest;
use App\Http\Requests\Api\RegisterUserRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @group Authentication
 *
 * Register, log in, read the current user, refresh and log out. Tokens are JWTs: send them as
 * `Authorization: Bearer {token}` on every other request.
 */
class AuthController extends Controller
{
    // Gives this controller the ok(), success() and error() response helpers
    use ApiResponses;

    /**
     * Register
     *
     * Creates the account and logs the user in immediately.
     *
     * @unauthenticated
     *
     * @response 200 {"data":{"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...","token_type":"bearer","expires_in":3600},"message":"Registered successfully","status":200}
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        // The password is hashed (bcrypt) before it is stored
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Generate a JWT for the new user using the "api" guard
        $token = Auth::guard('api')->login($user);

        return $this->ok('Registered successfully', $this->formatToken($token));
    }

    /**
     * Log in
     *
     * Returns an access token. Send it as a Bearer token on every other request.
     *
     * @unauthenticated
     *
     * @response 200 {"data":{"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...","token_type":"bearer","expires_in":3600},"message":"Login successful","status":200}
     * @response 401 scenario="Wrong email or password" {"message":"Invalid credentials","status":401}
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // attempt() returns false when the email or password is wrong
        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        return $this->ok('Login successful', $this->formatToken($token));
    }

    /**
     * Current user
     *
     * Returns the user that owns the Bearer token.
     *
     * @response 200 {"data":{"id":11,"name":"The Manager","email":"manager@manager.com","email_verified_at":"2026-11-23T14:53:00.000000Z","is_manager":true,"created_at":"2026-11-23T14:53:00.000000Z","updated_at":"2026-11-23T14:53:00.000000Z"},"message":"User retrieved","status":200}
     */
    public function me(): JsonResponse
    {
        return $this->ok('User retrieved', Auth::guard('api')->user());
    }

    /**
     * Refresh the token
     *
     * Blacklists the current token and returns a brand-new one.
     *
     * @response 200 {"data":{"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...","token_type":"bearer","expires_in":3600},"message":"Token refreshed","status":200}
     */
    public function refresh(): JsonResponse
    {
        return $this->ok('Token refreshed', $this->formatToken(Auth::guard('api')->refresh()));
    }

    /**
     * Log out
     *
     * Blacklists the current token so it can never be used again.
     *
     * @response 200 {"data":null,"message":"Successfully logged out","status":200}
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return $this->ok('Successfully logged out');
    }

    /**
     * Shapes the token the OAuth 2.0 way: the token, its type and its lifetime in seconds.
     *
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    protected function formatToken(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ];
    }
}
