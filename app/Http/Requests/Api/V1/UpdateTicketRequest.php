<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Permissions\V1\Abilities;
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
     * Validation rules for updating a ticket (deny by default).
     * "sometimes" means: validate the field only when it was sent.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'data.attributes.title' => ['sometimes', 'string', 'max:255'],
            'data.attributes.description' => ['sometimes', 'string'],
            'data.attributes.status' => ['sometimes', 'string', Rule::enum(TicketStatus::class)],
            'data.attributes.priority' => ['sometimes', 'string', Rule::enum(TicketPriority::class)],
            // Default: nobody may change the author or the assignee
            'data.relationships.author.data.id' => ['prohibited'],
            'data.relationships.assignee.data.id' => ['prohibited'],
        ];

        // Only the broad ability lifts the restriction (managers)
        if ($this->user()->tokenCan(Abilities::UpdateTicket)) {
            $rules['data.relationships.author.data.id'] = ['sometimes', 'integer', 'exists:users,id'];
            // null unassigns the ticket
            $rules['data.relationships.assignee.data.id'] = ['sometimes', 'nullable', 'integer', 'exists:users,id'];
        }

        return $rules;
    }
}
