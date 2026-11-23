<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Validates the body of PATCH /tickets/{ticket}: only the fields sent are checked.
 */
class UpdateTicketRequest extends BaseTicketRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * "sometimes" means: validate the field only when it was sent.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data.attributes.title' => ['sometimes', 'string', 'max:255'],
            'data.attributes.description' => ['sometimes', 'string'],
            'data.attributes.status' => ['sometimes', 'string', Rule::enum(TicketStatus::class)],
            'data.attributes.priority' => ['sometimes', 'string', Rule::enum(TicketPriority::class)],
            'data.relationships.author.data.id' => ['sometimes', 'integer', 'exists:users,id'],
            // null unassigns the ticket
            'data.relationships.assignee.data.id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
