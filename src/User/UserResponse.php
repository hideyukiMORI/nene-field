<?php

declare(strict_types=1);

namespace NeneField\User;

/**
 * Public JSON presenter for a {@see User}. The single place that decides which
 * user fields are exposed; `password_hash` is never included (used for API
 * responses and, later, sanitized audit snapshots — audit-logging.md §5).
 */
final readonly class UserResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(User $user): array
    {
        return [
            'user_id' => $user->userId,
            'organization_id' => $user->organizationId,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->isActive,
            'created_at' => $user->createdAt,
            'updated_at' => $user->updatedAt,
        ];
    }
}
