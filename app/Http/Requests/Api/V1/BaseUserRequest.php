<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

/**
 * Shared by every user request (store, replace, update).
 */
class BaseUserRequest extends FormRequest
{
    /**
     * Converts the nested JSON:API payload into columns of the users table.
     * Only keys that were actually sent are returned.
     *
     * @param  array<string, string>  $otherAttributes
     * @return array<string, mixed>
     */
    public function mappedAttributes(array $otherAttributes = []): array
    {
        // JSON path in the request  =>  column in the users table
        $attributeMap = array_merge([
            'data.attributes.name' => 'name',
            'data.attributes.email' => 'email',
            'data.attributes.isManager' => 'is_manager',
            'data.attributes.password' => 'password',
        ], $otherAttributes);

        $attributesToUpdate = [];

        foreach ($attributeMap as $key => $attribute) {
            if ($this->has($key)) {
                $value = $this->input($key);

                // Never store a plain password: hash it first
                if ($attribute === 'password') {
                    $value = Hash::make($value);
                }

                $attributesToUpdate[$attribute] = $value;
            }
        }

        return $attributesToUpdate;
    }

    /**
     * Descriptions and examples that Scribe shows in the documentation.
     *
     * @return array<string, array{description: string, example: mixed}>
     */
    public function bodyParameters(): array
    {
        return [
            'data.attributes.name' => [
                'description' => 'The user\'s full name.',
                'example' => 'New Staff',
            ],
            'data.attributes.email' => [
                'description' => 'A unique email address.',
                'example' => 'staff@example.com',
            ],
            'data.attributes.isManager' => [
                'description' => 'Whether the user is a manager.',
                'example' => false,
            ],
            'data.attributes.password' => [
                'description' => 'At least 8 characters. It is hashed before it is stored.',
                'example' => 'password123',
            ],
        ];
    }
}
