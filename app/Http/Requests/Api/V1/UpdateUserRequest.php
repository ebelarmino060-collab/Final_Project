<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Validates the body of PATCH /users/{user}: only the fields sent are checked.
 */
class UpdateUserRequest extends BaseUserRequest
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
            'data.attributes.name' => ['sometimes', 'string', 'max:255'],
            'data.attributes.email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'data.attributes.isManager' => ['sometimes', 'boolean'],
            'data.attributes.password' => ['sometimes', 'string', 'min:8'],
        ];
    }
}
