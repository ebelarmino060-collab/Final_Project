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
