<?php

declare(strict_types=1);

namespace NeneField\Tests\User;

use NeneField\Auth\Role;
use NeneField\User\CreateUserRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Boundary-value validation for the `POST /users` request parser. Pure function,
 * no I/O. Length limits are checked at exactly the limit (accepted) and one over
 * (rejected); the role enum rejects `superadmin` and unknown values.
 */
final class CreateUserRequestTest extends TestCase
{
    public function test_valid_minimal_body_has_no_errors(): void
    {
        $request = CreateUserRequest::parse([
            'name' => '田中',
            'email' => 'tanaka@example.com',
            'role' => 'submitter',
            'password' => 'passw0rd',
        ]);

        self::assertSame([], $request->errors);
        self::assertSame(Role::Submitter, $request->role);
    }

    public function test_all_required_fields_missing(): void
    {
        $request = CreateUserRequest::parse([]);

        self::assertSame(['name', 'email', 'role', 'password'], self::fields($request->errors));
        self::assertNull($request->role);
    }

    public function test_name_at_max_length_is_accepted_and_over_is_rejected(): void
    {
        $ok = CreateUserRequest::parse(self::body(['name' => str_repeat('a', 100)]));
        self::assertNotContains('name', self::fields($ok->errors));

        $over = CreateUserRequest::parse(self::body(['name' => str_repeat('a', 101)]));
        self::assertSame('too_long', self::codeFor($over->errors, 'name'));
    }

    public function test_password_at_min_length_is_accepted_and_under_is_rejected(): void
    {
        $ok = CreateUserRequest::parse(self::body(['password' => str_repeat('a', 8)]));
        self::assertNotContains('password', self::fields($ok->errors));

        $under = CreateUserRequest::parse(self::body(['password' => str_repeat('a', 7)]));
        self::assertSame('too_short', self::codeFor($under->errors, 'password'));
    }

    #[DataProvider('invalidEmails')]
    public function test_invalid_email_is_rejected(string $email): void
    {
        $request = CreateUserRequest::parse(self::body(['email' => $email]));
        self::assertSame('invalid_format', self::codeFor($request->errors, 'email'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidEmails(): iterable
    {
        yield 'no at' => ['plainaddress'];
        yield 'no domain' => ['user@'];
        yield 'no local' => ['@example.com'];
        yield 'spaces' => ['user name@example.com'];
    }

    #[DataProvider('assignableRoles')]
    public function test_assignable_roles_are_accepted(string $role, Role $expected): void
    {
        $request = CreateUserRequest::parse(self::body(['role' => $role]));
        self::assertSame([], $request->errors);
        self::assertSame($expected, $request->role);
    }

    /**
     * @return iterable<string, array{string, Role}>
     */
    public static function assignableRoles(): iterable
    {
        yield 'submitter' => ['submitter', Role::Submitter];
        yield 'approver' => ['approver', Role::Approver];
        yield 'admin' => ['admin', Role::Admin];
    }

    public function test_superadmin_role_is_rejected_at_request_layer(): void
    {
        $request = CreateUserRequest::parse(self::body(['role' => 'superadmin']));
        self::assertSame('invalid_value', self::codeFor($request->errors, 'role'));
        self::assertNull($request->role);
    }

    public function test_unknown_role_is_rejected(): void
    {
        $request = CreateUserRequest::parse(self::body(['role' => 'wizard']));
        self::assertSame('invalid_value', self::codeFor($request->errors, 'role'));
    }

    public function test_non_string_fields_are_treated_as_missing(): void
    {
        $request = CreateUserRequest::parse([
            'name' => 123,
            'email' => ['x'],
            'role' => false,
            'password' => 999,
        ]);

        self::assertSame(['name', 'email', 'role', 'password'], self::fields($request->errors));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function body(array $overrides): array
    {
        return array_merge([
            'name' => '田中',
            'email' => 'tanaka@example.com',
            'role' => 'submitter',
            'password' => 'passw0rd',
        ], $overrides);
    }

    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     * @return list<string>
     */
    private static function fields(array $errors): array
    {
        return array_map(static fn (array $e): string => $e['field'], $errors);
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
