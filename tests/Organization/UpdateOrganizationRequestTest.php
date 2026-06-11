<?php

declare(strict_types=1);

namespace NeneField\Tests\Organization;

use NeneField\Organization\UpdateOrganizationRequest;
use PHPUnit\Framework\TestCase;

/**
 * Boundary-value validation for the `PUT /organizations/{id}` request parser. The
 * three nullable settings expose a `*Provided` flag so an explicit clear-to-null
 * is distinguishable from an absent key.
 */
final class UpdateOrganizationRequestTest extends TestCase
{
    public function test_empty_body_provides_nothing(): void
    {
        $request = UpdateOrganizationRequest::parse([]);

        self::assertSame([], $request->errors);
        self::assertNull($request->name);
        self::assertNull($request->aiSummaryEnabled);
        self::assertFalse($request->notificationEmailProvided);
        self::assertFalse($request->webhookUrlProvided);
        self::assertFalse($request->customDomainProvided);
        self::assertNull($request->slug);
        self::assertNull($request->isActive);
    }

    public function test_name_boundary(): void
    {
        self::assertSame('required', self::codeFor(UpdateOrganizationRequest::parse(['name' => ''])->errors, 'name'));
        self::assertSame([], UpdateOrganizationRequest::parse(['name' => str_repeat('a', 100)])->errors);
        self::assertSame('too_long', self::codeFor(UpdateOrganizationRequest::parse(['name' => str_repeat('a', 101)])->errors, 'name'));
    }

    public function test_notification_email_present_null_clears(): void
    {
        $request = UpdateOrganizationRequest::parse(['notification_email' => null]);
        self::assertTrue($request->notificationEmailProvided);
        self::assertNull($request->notificationEmail);
        self::assertSame([], $request->errors);
    }

    public function test_notification_email_blank_clears(): void
    {
        $request = UpdateOrganizationRequest::parse(['notification_email' => '  ']);
        self::assertTrue($request->notificationEmailProvided);
        self::assertNull($request->notificationEmail);
    }

    public function test_notification_email_valid_and_invalid(): void
    {
        $ok = UpdateOrganizationRequest::parse(['notification_email' => 'ops@example.com']);
        self::assertSame([], $ok->errors);
        self::assertSame('ops@example.com', $ok->notificationEmail);

        $bad = UpdateOrganizationRequest::parse(['notification_email' => 'not-an-email']);
        self::assertSame('invalid_format', self::codeFor($bad->errors, 'notification_email'));
    }

    public function test_webhook_url_clear_and_set(): void
    {
        $cleared = UpdateOrganizationRequest::parse(['webhook_url' => null]);
        self::assertTrue($cleared->webhookUrlProvided);
        self::assertNull($cleared->webhookUrl);

        $set = UpdateOrganizationRequest::parse(['webhook_url' => 'https://hooks.example.com/x']);
        self::assertSame('https://hooks.example.com/x', $set->webhookUrl);
    }

    public function test_ai_summary_enabled_and_is_active_must_be_boolean(): void
    {
        self::assertSame('invalid_value', self::codeFor(UpdateOrganizationRequest::parse(['ai_summary_enabled' => 'x'])->errors, 'ai_summary_enabled'));
        self::assertSame('invalid_value', self::codeFor(UpdateOrganizationRequest::parse(['is_active' => 1])->errors, 'is_active'));

        $ok = UpdateOrganizationRequest::parse(['ai_summary_enabled' => true, 'is_active' => false]);
        self::assertSame([], $ok->errors);
        self::assertTrue($ok->aiSummaryEnabled);
        self::assertFalse($ok->isActive);
    }

    public function test_slug_grammar_is_enforced(): void
    {
        self::assertSame('invalid_format', self::codeFor(UpdateOrganizationRequest::parse(['slug' => 'Bad_Slug'])->errors, 'slug'));
        self::assertSame('invalid_format', self::codeFor(UpdateOrganizationRequest::parse(['slug' => ''])->errors, 'slug'));
        self::assertSame('invalid_format', self::codeFor(UpdateOrganizationRequest::parse(['slug' => str_repeat('a', 101)])->errors, 'slug'));

        $ok = UpdateOrganizationRequest::parse(['slug' => 'renamed-1']);
        self::assertSame([], $ok->errors);
        self::assertSame('renamed-1', $ok->slug);
    }

    public function test_custom_domain_clear(): void
    {
        $request = UpdateOrganizationRequest::parse(['custom_domain' => null]);
        self::assertTrue($request->customDomainProvided);
        self::assertNull($request->customDomain);
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
