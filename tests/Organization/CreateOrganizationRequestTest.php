<?php

declare(strict_types=1);

namespace NeneField\Tests\Organization;

use NeneField\Organization\CreateOrganizationRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Boundary-value validation for the `POST /organizations` request parser. Focuses
 * on the slug grammar (`^[a-z0-9-]+$`), the length limits at the edge, and the
 * `is_active` default.
 */
final class CreateOrganizationRequestTest extends TestCase
{
    public function test_valid_body(): void
    {
        $request = CreateOrganizationRequest::parse(['name' => '山田造園', 'slug' => 'yamada-1']);

        self::assertSame([], $request->errors);
        self::assertSame('yamada-1', $request->slug);
        self::assertTrue($request->isActive, 'is_active defaults to true');
        self::assertNull($request->customDomain);
    }

    public function test_name_and_slug_required(): void
    {
        $request = CreateOrganizationRequest::parse([]);
        self::assertSame('required', self::codeFor($request->errors, 'name'));
        self::assertSame('required', self::codeFor($request->errors, 'slug'));
    }

    public function test_name_at_max_ok_over_rejected(): void
    {
        self::assertNull(self::codeFor(CreateOrganizationRequest::parse(['name' => str_repeat('a', 100), 'slug' => 'x'])->errors, 'name'));
        self::assertSame('too_long', self::codeFor(CreateOrganizationRequest::parse(['name' => str_repeat('a', 101), 'slug' => 'x'])->errors, 'name'));
    }

    public function test_slug_at_max_ok_over_rejected(): void
    {
        self::assertNull(self::codeFor(CreateOrganizationRequest::parse(['name' => 'n', 'slug' => str_repeat('a', 100)])->errors, 'slug'));
        self::assertSame('too_long', self::codeFor(CreateOrganizationRequest::parse(['name' => 'n', 'slug' => str_repeat('a', 101)])->errors, 'slug'));
    }

    #[DataProvider('validSlugs')]
    public function test_valid_slugs_pass(string $slug): void
    {
        self::assertNull(self::codeFor(CreateOrganizationRequest::parse(['name' => 'n', 'slug' => $slug])->errors, 'slug'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validSlugs(): iterable
    {
        yield 'lowercase' => ['acme'];
        yield 'with digits' => ['acme123'];
        yield 'with hyphen' => ['a-b-c'];
        yield 'single char' => ['a'];
        yield 'digits only' => ['2026'];
    }

    #[DataProvider('invalidSlugs')]
    public function test_invalid_slugs_are_rejected(string $slug): void
    {
        self::assertSame('invalid_format', self::codeFor(CreateOrganizationRequest::parse(['name' => 'n', 'slug' => $slug])->errors, 'slug'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSlugs(): iterable
    {
        yield 'uppercase' => ['Acme'];
        yield 'underscore' => ['a_b'];
        yield 'space' => ['a b'];
        yield 'symbol' => ['a!b'];
        yield 'non-ascii' => ['やまだ'];
        yield 'internal space' => ['ab cd'];
    }

    public function test_surrounding_whitespace_is_trimmed_before_validation(): void
    {
        $request = CreateOrganizationRequest::parse(['name' => 'n', 'slug' => '  acme  ']);
        self::assertSame([], $request->errors);
        self::assertSame('acme', $request->slug);
    }

    public function test_custom_domain_blank_becomes_null(): void
    {
        self::assertNull(CreateOrganizationRequest::parse(['name' => 'n', 'slug' => 'x', 'custom_domain' => '   '])->customDomain);
        self::assertSame('vanity.example.com', CreateOrganizationRequest::parse(['name' => 'n', 'slug' => 'x', 'custom_domain' => 'vanity.example.com'])->customDomain);
    }

    public function test_is_active_explicit_false_and_non_bool_default(): void
    {
        self::assertFalse(CreateOrganizationRequest::parse(['name' => 'n', 'slug' => 'x', 'is_active' => false])->isActive);
        self::assertTrue(CreateOrganizationRequest::parse(['name' => 'n', 'slug' => 'x', 'is_active' => 'no'])->isActive, 'non-bool falls back to the default');
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
