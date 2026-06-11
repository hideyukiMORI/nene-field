<?php

declare(strict_types=1);

namespace NeneField\Organization;

/**
 * Parses + format-validates the `POST /organizations` request body (superadmin
 * provisioning). Slug uniqueness is a business invariant enforced in
 * {@see CreateOrganizationUseCase}; the format (`^[a-z0-9-]+$`) is checked here.
 */
final readonly class CreateOrganizationRequest
{
    private const MAX_NAME = 100;
    private const MAX_SLUG = 100;

    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $customDomain,
        public bool $isActive,
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
        $slug = is_string($body['slug'] ?? null) ? trim((string) $body['slug']) : '';

        if ($name === '') {
            $errors[] = self::error('name', 'name is required.', 'required');
        } elseif (mb_strlen($name) > self::MAX_NAME) {
            $errors[] = self::error('name', 'name is too long.', 'too_long');
        }

        if ($slug === '') {
            $errors[] = self::error('slug', 'slug is required.', 'required');
        } elseif (mb_strlen($slug) > self::MAX_SLUG) {
            $errors[] = self::error('slug', 'slug is too long.', 'too_long');
        } elseif (preg_match('/\A[a-z0-9-]+\z/', $slug) !== 1) {
            $errors[] = self::error('slug', 'slug must match ^[a-z0-9-]+$.', 'invalid_format');
        }

        $customDomain = is_string($body['custom_domain'] ?? null) && trim((string) $body['custom_domain']) !== ''
            ? trim((string) $body['custom_domain'])
            : null;

        $isActive = array_key_exists('is_active', $body) && is_bool($body['is_active'])
            ? $body['is_active']
            : true;

        return new self($name, $slug, $customDomain, $isActive, $errors);
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
