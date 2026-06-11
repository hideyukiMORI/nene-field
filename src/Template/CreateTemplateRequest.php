<?php

declare(strict_types=1);

namespace NeneField\Template;

/**
 * Parses + format-validates the `POST /templates` request body. `fields` is
 * parsed by {@see TemplateFieldsRequest}; the default-uniqueness invariant is
 * enforced in {@see CreateTemplateUseCase}.
 */
final readonly class CreateTemplateRequest
{
    private const MAX_NAME = 100;

    /**
     * @param list<TemplateField>                                        $fields
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public array $fields,
        public bool $isDefault,
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

        if ($name === '') {
            $errors[] = self::error('name', 'name is required.', 'required');
        } elseif (mb_strlen($name) > self::MAX_NAME) {
            $errors[] = self::error('name', 'name is too long.', 'too_long');
        }

        if (!array_key_exists('fields', $body)) {
            $errors[] = self::error('fields', 'fields is required.', 'required');
            $fields = [];
        } else {
            [$fields, $fieldErrors] = TemplateFieldsRequest::parse($body);
            $errors = array_merge($errors, $fieldErrors);
        }

        $description = is_string($body['description'] ?? null) && trim((string) $body['description']) !== ''
            ? trim((string) $body['description'])
            : null;

        $isDefault = array_key_exists('is_default', $body) && is_bool($body['is_default'])
            ? $body['is_default']
            : false;

        return new self($name, $description, $fields, $isDefault, $errors);
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
