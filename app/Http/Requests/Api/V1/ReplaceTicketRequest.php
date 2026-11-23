<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Validates the body of PUT /tickets/{ticket}: every field is required.
 */
class ReplaceTicketRequest extends BaseTicketRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * PUT replaces the whole resource, so nothing may be missing.
     * The assignee must be sent too, but may be null to unassign the ticket.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.attributes' => ['required', 'array'],
            'data.attributes.title' => ['required', 'string', 'max:255'],
            'data.attributes.description' => ['required', 'string'],
            'data.attributes.status' => ['required', 'string', Rule::enum(TicketStatus::class)],
            'data.attributes.priority' => ['required', 'string', Rule::enum(TicketPriority::class)],
            'data.relationships' => ['required', 'array'],
            'data.relationships.author' => ['required', 'array'],
            'data.relationships.author.data' => ['required', 'array'],
            'data.relationships.author.data.id' => ['required', 'integer', 'exists:users,id'],
            'data.relationships.assignee' => ['required', 'array'],
            'data.relationships.assignee.data' => ['required', 'array'],
            'data.relationships.assignee.data.id' => ['present', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
