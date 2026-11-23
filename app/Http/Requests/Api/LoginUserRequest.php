<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the data sent to POST /api/v1/auth/login.
 */
class LoginUserRequest extends FormRequest
{
    /**
     * Anyone may try to log in, so authorization always passes.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
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
            'email' => [
                'description' => 'Your email address.',
                'example' => 'manager@manager.com',
            ],
            'password' => [
                'description' => 'Your password.',
                'example' => 'password',
            ],
        ];
    }
}
