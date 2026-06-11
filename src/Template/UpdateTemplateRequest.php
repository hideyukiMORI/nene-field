<?php

declare(strict_types=1);

namespace NeneField\Template;

/**
 * Parses + format-validates the `PUT /templates/{id}` request body. Every field
 * is optional (partial update): an absent key keeps the existing value, so a
 * `null` property / `false` provided-flag means "not provided".
 */
final readonly class UpdateTemplateRequest
{
    private const MAX_NAME = 100;

    /**
     * @param list<TemplateField>|null                                   $fields
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public ?string $name,
        public bool $descriptionProvided,
        public ?string $description,
        public ?array $fields,
        public ?bool $isDefault,
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

        $descriptionProvided = array_key_exists('description', $body);
        $description = null;
        if ($descriptionProvided && is_string($body['description']) && trim($body['description']) !== '') {
            $description = trim($body['description']);
        }

        $fields = null;
        if (array_key_exists('fields', $body)) {
            [$fields, $fieldErrors] = TemplateFieldsRequest::parse($body);
            $errors = array_merge($errors, $fieldErrors);
        }

        $isDefault = null;
        if (array_key_exists('is_default', $body)) {
            if (!is_bool($body['is_default'])) {
                $errors[] = self::error('is_default', 'is_default must be a boolean.', 'invalid_value');
            } else {
                $isDefault = $body['is_default'];
            }
        }

        return new self($name, $descriptionProvided, $description, $fields, $isDefault, $errors);
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
