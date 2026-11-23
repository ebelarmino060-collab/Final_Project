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

class AuthController extends Controller
{
    // Gives this controller the ok(), success() and error() response helpers
    use ApiResponses;

    /**
     * Creates the account and logs the user in immediately.
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
     * Checks the credentials and returns a token when they are correct.
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
     * Returns the user that owns the Bearer token.
     */
    public function me(): JsonResponse
    {
        return $this->ok('User retrieved', Auth::guard('api')->user());
    }

    /**
     * Blacklists the current token and returns a brand-new one.
     */
    public function refresh(): JsonResponse
    {
        return $this->ok('Token refreshed', $this->formatToken(Auth::guard('api')->refresh()));
    }

    /**
     * Blacklists the current token so it can never be used again.
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
