<?php

declare(strict_types=1);

namespace NeneField\Organization;

/**
 * Parses + format-validates the `PUT /organizations/{id}` request body. Every
 * field is optional (partial update). Nullable settings
 * (`notification_email` / `webhook_url` / `custom_domain`) expose a `*Provided`
 * flag so they can be explicitly cleared to `null`.
 *
 * `slug`, `custom_domain`, and `is_active` are superadmin-only; role gating is
 * applied in the use case. The write-only AI secret fields (`ai_api_url` /
 * `ai_api_key`) are intentionally ignored here (out of scope; Phase 3).
 */
final readonly class UpdateOrganizationRequest
{
    private const MAX_NAME = 100;
    private const MAX_SLUG = 100;

    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public ?string $name,
        public ?bool $aiSummaryEnabled,
        public bool $notificationEmailProvided,
        public ?string $notificationEmail,
        public bool $webhookUrlProvided,
        public ?string $webhookUrl,
        public ?string $slug,
        public bool $customDomainProvided,
        public ?string $customDomain,
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

        $aiSummaryEnabled = self::boolField($body, 'ai_summary_enabled', $errors);

        [$notificationEmailProvided, $notificationEmail] = self::nullableString($body, 'notification_email');
        if ($notificationEmail !== null && filter_var($notificationEmail, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = self::error('notification_email', 'notification_email must be a valid address.', 'invalid_format');
        }

        [$webhookUrlProvided, $webhookUrl] = self::nullableString($body, 'webhook_url');

        $slug = null;
        if (array_key_exists('slug', $body)) {
            $slug = is_string($body['slug']) ? trim($body['slug']) : '';
            if ($slug === '' || mb_strlen($slug) > self::MAX_SLUG || preg_match('/\A[a-z0-9-]+\z/', $slug) !== 1) {
                $errors[] = self::error('slug', 'slug must match ^[a-z0-9-]+$.', 'invalid_format');
            }
        }

        [$customDomainProvided, $customDomain] = self::nullableString($body, 'custom_domain');

        $isActive = self::boolField($body, 'is_active', $errors);

        return new self(
            name: $name,
            aiSummaryEnabled: $aiSummaryEnabled,
            notificationEmailProvided: $notificationEmailProvided,
            notificationEmail: $notificationEmail,
            webhookUrlProvided: $webhookUrlProvided,
            webhookUrl: $webhookUrl,
            slug: $slug,
            customDomainProvided: $customDomainProvided,
            customDomain: $customDomain,
            isActive: $isActive,
            errors: $errors,
        );
    }

    /**
     * @param array<string, mixed>                                       $body
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    private static function boolField(array $body, string $key, array &$errors): ?bool
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }

        if (!is_bool($body[$key])) {
            $errors[] = self::error($key, sprintf('%s must be a boolean.', $key), 'invalid_value');

            return null;
        }

        return $body[$key];
    }

    /**
     * Returns `[provided, value]`. A present `null` or empty string clears the
     * field to `null`; a present non-empty string trims to that value.
     *
     * @param array<string, mixed> $body
     * @return array{0: bool, 1: ?string}
     */
    private static function nullableString(array $body, string $key): array
    {
        if (!array_key_exists($key, $body)) {
            return [false, null];
        }

        $value = $body[$key];

        if (is_string($value) && trim($value) !== '') {
            return [true, trim($value)];
        }

        return [true, null];
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
