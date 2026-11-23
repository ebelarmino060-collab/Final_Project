<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Validates the body of POST /tickets and POST /users/{user}/tickets.
 */
class StoreTicketRequest extends BaseTicketRequest
{
    /**
     * Who may send this request is decided later by TicketPolicy (Lesson 19),
     * so this class always allows it.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a ticket.
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
            // Optional on create: a new ticket is open with medium priority unless told otherwise
            'data.attributes.status' => ['sometimes', 'string', Rule::enum(TicketStatus::class)],
            'data.attributes.priority' => ['sometimes', 'string', Rule::enum(TicketPriority::class)],
            $this->authorIdAttribute() => ['required', 'integer', 'exists:users,id'],
            'data.relationships.assignee.data.id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Where the author id comes from: the body on /tickets,
     * or the URL (merged into "author" below) on /users/{user}/tickets.
     */
    protected function authorIdAttribute(): string
    {
        return $this->routeIs('tickets.store') ? 'data.relationships.author.data.id' : 'author';
    }

    /**
     * Runs before validation. On /users/{user}/tickets the author id is in the URL,
     * so it is copied into an "author" field that the rules above can validate.
     */
    protected function prepareForValidation(): void
    {
        if ($this->routeIs('users.tickets.store')) {
            $this->merge([
                'author' => $this->route('user')->getKey(),
            ]);
        }
    }
}
