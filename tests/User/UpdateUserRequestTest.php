<?php

declare(strict_types=1);

namespace NeneField\Tests\User;

use NeneField\Auth\Role;
use NeneField\User\UpdateUserRequest;
use PHPUnit\Framework\TestCase;

/**
 * Boundary-value validation for the `PUT /users/{id}` request parser. Every field
 * is optional; an absent key parses to `null` ("keep"), a present invalid value
 * is an error. `email` is immutable and ignored.
 */
final class UpdateUserRequestTest extends TestCase
{
    public function test_empty_body_keeps_everything(): void
    {
        $request = UpdateUserRequest::parse([]);

        self::assertSame([], $request->errors);
        self::assertNull($request->name);
        self::assertNull($request->role);
        self::assertNull($request->isActive);
    }

    public function test_present_name_empty_is_rejected(): void
    {
        $request = UpdateUserRequest::parse(['name' => '   ']);
        self::assertSame('required', self::codeFor($request->errors, 'name'));
    }

    public function test_name_at_max_length_ok_over_rejected(): void
    {
        self::assertSame([], UpdateUserRequest::parse(['name' => str_repeat('a', 100)])->errors);
        self::assertSame('too_long', self::codeFor(UpdateUserRequest::parse(['name' => str_repeat('a', 101)])->errors, 'name'));
    }

    public function test_role_superadmin_and_unknown_are_rejected(): void
    {
        self::assertSame('invalid_value', self::codeFor(UpdateUserRequest::parse(['role' => 'superadmin'])->errors, 'role'));
        self::assertSame('invalid_value', self::codeFor(UpdateUserRequest::parse(['role' => 'ghost'])->errors, 'role'));
    }

    public function test_valid_role_parses(): void
    {
        $request = UpdateUserRequest::parse(['role' => 'approver']);
        self::assertSame([], $request->errors);
        self::assertSame(Role::Approver, $request->role);
    }

    public function test_is_active_must_be_boolean(): void
    {
        self::assertSame('invalid_value', self::codeFor(UpdateUserRequest::parse(['is_active' => 'yes'])->errors, 'is_active'));

        $request = UpdateUserRequest::parse(['is_active' => false]);
        self::assertSame([], $request->errors);
        self::assertFalse($request->isActive);
    }

    public function test_email_is_ignored(): void
    {
        $request = UpdateUserRequest::parse(['email' => 'new@example.com', 'name' => '改名']);
        self::assertSame([], $request->errors);
        self::assertSame('改名', $request->name);
    }

    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    private static function codeFor(array $errors, string $field): ?string
    {
        foreach ($errors as $error) {
            if ($error['field'] === $field) {
                return $error['code'];
            }
        }

        return null;
    }
}
