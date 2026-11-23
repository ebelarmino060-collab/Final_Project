<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by every ticket request (store, replace, update).
 */
class BaseTicketRequest extends FormRequest
{
    /**
     * Converts the nested JSON:API payload into columns of the tickets table.
     * Only keys that were actually sent are returned, which is what makes PATCH work.
     *
     * Example: data.attributes.title  ->  title
     *
     * @param  array<string, string>  $otherAttributes
     * @return array<string, mixed>
     */
    public function mappedAttributes(array $otherAttributes = []): array
    {
        // JSON path in the request  =>  column in the tickets table
        $attributeMap = array_merge([
            'data.attributes.title' => 'title',
            'data.attributes.description' => 'description',
            'data.attributes.status' => 'status',
            'data.attributes.priority' => 'priority',
            'data.relationships.author.data.id' => 'created_by',
            'data.relationships.assignee.data.id' => 'assigned_to',
        ], $otherAttributes);

        $attributesToUpdate = [];

        foreach ($attributeMap as $key => $attribute) {
            // Keep the value only if the client sent that field
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
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
            'data.attributes.title' => [
                'description' => 'A short summary of the problem.',
                'example' => 'Printer on the second floor is offline',
            ],
            'data.attributes.description' => [
                'description' => 'The full description of the problem.',
                'example' => 'The printer shows error E-104 and does not accept print jobs.',
            ],
            'data.attributes.status' => [
                'description' => 'The ticket status: '.implode(', ', TicketStatus::values()).'.',
                'example' => TicketStatus::Open->value,
            ],
            'data.attributes.priority' => [
                'description' => 'The ticket priority: '.implode(', ', TicketPriority::values()).'.',
                'example' => TicketPriority::High->value,
            ],
            'data.relationships.author.data.id' => [
                'description' => 'The id of the user who raised the ticket. Regular users must use their own id.',
                'example' => 1,
            ],
            'data.relationships.assignee.data.id' => [
                'description' => 'The id of the user the ticket is assigned to, or null to unassign.',
                'example' => 2,
            ],
        ];
    }

    /**
     * Custom validation messages. The key format is "field.rule".
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data.attributes.status.enum' => 'The data.attributes.status value is invalid. Please use '.implode(', ', TicketStatus::values()).'.',
            'data.attributes.priority.enum' => 'The data.attributes.priority value is invalid. Please use '.implode(', ', TicketPriority::values()).'.',
        ];
    }
}
