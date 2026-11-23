<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 10 fake users
        $users = User::factory(10)->create();

        // Create 100 fake tickets spread across those 10 users; most are assigned to one of them
        Ticket::factory(100)
            ->recycle($users)
            ->state(fn (): array => [
                'assigned_to' => fake()->boolean(70) ? $users->random()->id : null,
            ])
            ->create();
    }
}
