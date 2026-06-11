<?php

declare(strict_types=1);

namespace NeneField\User;

use NeneField\Auth\Role;

/**
 * Parses + format-validates the `POST /users` request body (handler-layer format
 * validation → readonly values; business invariants such as email uniqueness and
 * role-assignability stay in {@see CreateUserUseCase}).
 *
 * `superadmin` is rejected here: the create endpoint only assigns the
 * organization-scoped roles allowed by the OpenAPI enum.
 */
final readonly class CreateUserRequest
{
    private const MAX_NAME = 100;
    private const MIN_PASSWORD = 8;

    /** Roles a tenant admin may assign via the users API. */
    private const ASSIGNABLE_ROLES = ['submitter', 'approver', 'admin'];

    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?Role $role,
        public string $password,
        public array $errors,
    ) {
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function parse(array $body): self
    {
        $errors = [];

        $name = is_string($body['name'] ?? null) ? trim((string) $body['name']) : '';
        $email = is_string($body['email'] ?? null) ? trim((string) $body['email']) : '';
        $rawRole = is_string($body['role'] ?? null) ? (string) $body['role'] : '';
        $password = is_string($body['password'] ?? null) ? (string) $body['password'] : '';

        if ($name === '') {
            $errors[] = self::error('name', 'name is required.', 'required');
        } elseif (mb_strlen($name) > self::MAX_NAME) {
            $errors[] = self::error('name', 'name is too long.', 'too_long');
        }

        if ($email === '') {
            $errors[] = self::error('email', 'email is required.', 'required');
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = self::error('email', 'email must be a valid address.', 'invalid_format');
        }

        $role = null;
        if ($rawRole === '') {
            $errors[] = self::error('role', 'role is required.', 'required');
        } elseif (!in_array($rawRole, self::ASSIGNABLE_ROLES, true)) {
            $errors[] = self::error('role', 'role must be one of: submitter, approver, admin.', 'invalid_value');
        } else {
            $role = Role::from($rawRole);
        }

        if ($password === '') {
            $errors[] = self::error('password', 'password is required.', 'required');
        } elseif (mb_strlen($password) < self::MIN_PASSWORD) {
            $errors[] = self::error('password', 'password must be at least 8 characters.', 'too_short');
        }

        return new self($name, $email, $role, $password, $errors);
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
