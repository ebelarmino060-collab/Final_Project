<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Permissions\V1\Abilities;
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
     * Validation rules for creating a ticket (deny by default).
     *
     * Null-safe so Scribe can read the rules while no user is logged in: it then sees the
     * full (manager) rule set. Real requests always have a user because every ticket
     * route sits behind auth:api.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $authorIdAttribute = $this->authorIdAttribute();
        $user = $this->user();
        $authorRule = ['required', 'integer', 'exists:users,id'];
        $assigneeRule = ['sometimes', 'nullable', 'integer', 'exists:users,id'];

        $rules = [
            'data' => ['required', 'array'],
            'data.attributes' => ['required', 'array'],
            'data.attributes.title' => ['required', 'string', 'max:255'],
            'data.attributes.description' => ['required', 'string'],
            // Optional on create: a new ticket is open with medium priority unless told otherwise
            'data.attributes.status' => ['sometimes', 'string', Rule::enum(TicketStatus::class)],
            'data.attributes.priority' => ['sometimes', 'string', Rule::enum(TicketPriority::class)],
            // Default: the author must be the logged-in user (size:5 means the id must equal 5)
            $authorIdAttribute => $user ? [...$authorRule, 'size:'.$user->id] : $authorRule,
            // Default: nobody may assign the new ticket
            'data.relationships.assignee.data.id' => $user ? ['prohibited'] : $assigneeRule,
        ];

        // Only the broad ability lifts both restrictions (managers)
        if ($user?->tokenCan(Abilities::CreateTicket)) {
            $rules[$authorIdAttribute] = $authorRule;
            $rules['data.relationships.assignee.data.id'] = $assigneeRule;
        }

        return $rules;
    }

    /**
     * Descriptions and examples that Scribe shows in the documentation.
     *
     * @return array<string, array{description: string, example: mixed}>
     */
    public function bodyParameters(): array
    {
        $documentation = parent::bodyParameters();

        // The author id is in the body on /tickets, and in the URL on /users/{user}/tickets
        if ($this->authorIdAttribute() === 'author') {
            unset($documentation['data.relationships.author.data.id']);
            $documentation['author'] = [
                'description' => 'The author id. It is taken from the URL, so repeat the {user} path value.',
                'example' => 1,
            ];
        }

        $documentation['data.relationships.assignee.data.id']['description'] .= ' Managers only: regular users may not assign a new ticket.';

        return $documentation;
    }

    /**
     * Where the author id comes from: the body on /tickets,
     * or the URL (merged into "author" below) on /users/{user}/tickets.
     */
    protected function authorIdAttribute(): string
    {
        return $this->routeIs('users.tickets.store') ? 'author' : 'data.relationships.author.data.id';
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
