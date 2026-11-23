<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the body of POST /users.
 */
class StoreUserRequest extends BaseUserRequest
{
    /**
     * Who may create users is decided by UserPolicy in the controller.
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
            'data' => ['required', 'array'],
            'data.attributes' => ['required', 'array'],
            'data.attributes.name' => ['required', 'string', 'max:255'],
            // unique:users,email rejects an email that is already taken
            'data.attributes.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'data.attributes.isManager' => ['required', 'boolean'],
            'data.attributes.password' => ['required', 'string', 'min:8'],
        ];
    }
}
