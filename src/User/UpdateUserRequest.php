<?php

declare(strict_types=1);

namespace NeneField\User;

use NeneField\Auth\Role;

/**
 * Parses + format-validates the `PUT /users/{user_id}` request body. Every field
 * is optional (partial update): an absent key leaves the value unchanged, so a
 * `null` property here means "not provided". `email` is immutable and ignored.
 */
final readonly class UpdateUserRequest
{
    private const MAX_NAME = 100;

    /** Roles a tenant admin may assign via the users API. */
    private const ASSIGNABLE_ROLES = ['submitter', 'approver', 'admin'];

    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public ?string $name,
        public ?Role $role,
        public ?bool $isActive,
        public array $errors,
    ) {
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function parse(array $body): self
    {
        $errors = [];

        $name = null;
        if (array_key_exists('name', $body)) {
            $name = is_string($body['name']) ? trim($body['name']) : '';
            if ($name === '') {
                $errors[] = self::error('name', 'name must not be empty.', 'required');
            } elseif (mb_strlen($name) > self::MAX_NAME) {
                $errors[] = self::error('name', 'name is too long.', 'too_long');
            }
        }

        $role = null;
        if (array_key_exists('role', $body)) {
            $rawRole = is_string($body['role']) ? $body['role'] : '';
            if (!in_array($rawRole, self::ASSIGNABLE_ROLES, true)) {
                $errors[] = self::error('role', 'role must be one of: submitter, approver, admin.', 'invalid_value');
            } else {
                $role = Role::from($rawRole);
            }
        }

        $isActive = null;
        if (array_key_exists('is_active', $body)) {
            if (!is_bool($body['is_active'])) {
                $errors[] = self::error('is_active', 'is_active must be a boolean.', 'invalid_value');
            } else {
                $isActive = $body['is_active'];
            }
        }

        return new self($name, $role, $isActive, $errors);
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
