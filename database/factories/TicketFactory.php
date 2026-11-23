<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds fake Ticket rows for seeding and testing.
 *
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * The default values of one fake ticket.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Set here too because seeders may run without model events
            'ticket_number' => Ticket::generateTicketNumber(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(TicketStatus::cases()),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            // Creates a user for the ticket unless one is supplied (see recycle())
            'created_by' => User::factory(),
            'assigned_to' => null,
        ];
    }

    /**
     * A ticket that is assigned to a user.
     */
    public function assigned(?User $assignee = null): static
    {
        return $this->state(fn (): array => [
            'assigned_to' => $assignee ?? User::factory(),
        ]);
    }
}
