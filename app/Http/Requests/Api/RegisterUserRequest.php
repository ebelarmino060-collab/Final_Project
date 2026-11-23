<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the data sent to POST /api/v1/auth/register.
 */
class RegisterUserRequest extends FormRequest
{
    /**
     * Anyone may register, so authorization always passes.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The validation rules. On failure Laravel stops here and returns 422.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // unique:users,email rejects an email that is already registered
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // confirmed requires a matching password_confirmation field
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Descriptions and examples that Scribe shows in the documentation.
     *
     * @return array<string, array{description: string, example: mixed}>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Your full name.',
                'example' => 'Jane Doe',
            ],
            'email' => [
                'description' => 'Your email address. It must not be registered yet.',
                'example' => 'jane@example.com',
            ],
            'password' => [
                'description' => 'At least 8 characters.',
                'example' => 'password',
            ],
            'password_confirmation' => [
                'description' => 'The same password again.',
                'example' => 'password',
            ],
        ];
    }
}
