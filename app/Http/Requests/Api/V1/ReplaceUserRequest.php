<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Validates the body of PUT /users/{user}: every field is required.
 */
class ReplaceUserRequest extends BaseUserRequest
{
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
            'data.attributes.email' => [
                'required',
                'email',
                'max:255',
                // The email must be unique, except for the user being replaced
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'data.attributes.isManager' => ['required', 'boolean'],
            'data.attributes.password' => ['required', 'string', 'min:8'],
        ];
    }
}
