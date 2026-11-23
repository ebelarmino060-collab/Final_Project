<?php

namespace App\Permissions\V1;

use App\Models\User;

/**
 * Every ability name in one place, plus the rule that decides who receives which.
 * Format: resource:action. "own" means "only for things I own" (tickets I raised).
 */
final class Abilities
{
    // Ticket abilities
    public const CreateTicket = 'ticket:create';

    public const CreateOwnTicket = 'ticket:own:create';

    public const UpdateTicket = 'ticket:update';

    public const UpdateOwnTicket = 'ticket:own:update';

    public const ReplaceTicket = 'ticket:replace';

    public const DeleteTicket = 'ticket:delete';

    public const DeleteOwnTicket = 'ticket:own:delete';

    // User management abilities (managers only)
    public const CreateUser = 'user:create';

    public const UpdateUser = 'user:update';

    public const ReplaceUser = 'user:replace';

    public const DeleteUser = 'user:delete';

    /**
     * The abilities a user receives. The list is written into the JWT at login,
     * so the policies can read it without querying the database.
     *
     * @return list<string>
     */
    public static function getAbilities(User $user): array
    {
        // Managers may act on any ticket and manage users
        if ($user->is_manager) {
            return [
                self::CreateTicket,
                self::UpdateTicket,
                self::ReplaceTicket,
                self::DeleteTicket,
                self::CreateUser,
                self::UpdateUser,
                self::ReplaceUser,
                self::DeleteUser,
            ];
        }

        // Regular users may only act on the tickets they raised
        return [
            self::CreateOwnTicket,
            self::UpdateOwnTicket,
            self::DeleteOwnTicket,
        ];
    }
}
