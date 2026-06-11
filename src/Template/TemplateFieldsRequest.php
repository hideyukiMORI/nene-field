<?php

declare(strict_types=1);

namespace NeneField\Template;

/**
 * Parses + format-validates the `fields` array shared by the template create and
 * update request bodies (OpenAPI `TemplateFieldDefinition[]`). Returns the parsed
 * {@see TemplateField} list together with any per-field validation errors.
 */
final readonly class TemplateFieldsRequest
{
    private const MAX_NAME = 100;
    private const MAX_LABEL = 200;

    /**
     * @param array<string, mixed> $body
     * @return array{0: list<TemplateField>, 1: list<array{field: string, message: string, code: string}>}
     */
    public static function parse(array $body): array
    {
        $raw = $body['fields'] ?? null;

        if (!is_array($raw) || !array_is_list($raw)) {
            return [[], [self::error('fields', 'fields must be an array.', 'invalid_type')]];
        }

        $fields = [];
        $errors = [];

        foreach ($raw as $index => $item) {
            $path = "fields.{$index}";

            if (!is_array($item)) {
                $errors[] = self::error($path, 'field must be an object.', 'invalid_type');

                continue;
            }

            $name = is_string($item['name'] ?? null) ? trim($item['name']) : '';
            $label = is_string($item['label'] ?? null) ? trim($item['label']) : '';
            $type = TemplateFieldType::tryFrom(is_string($item['type'] ?? null) ? $item['type'] : '');
            $required = $item['required'] ?? null;

            if ($name === '') {
                $errors[] = self::error("{$path}.name", 'name is required.', 'required');
            } elseif (mb_strlen($name) > self::MAX_NAME) {
                $errors[] = self::error("{$path}.name", 'name is too long.', 'too_long');
            }

            if ($label === '') {
                $errors[] = self::error("{$path}.label", 'label is required.', 'required');
            } elseif (mb_strlen($label) > self::MAX_LABEL) {
                $errors[] = self::error("{$path}.label", 'label is too long.', 'too_long');
            }

            if ($type === null) {
                $errors[] = self::error("{$path}.type", 'type must be one of: text, textarea, number, checkbox, date, select.', 'invalid_value');
            }

            if (!is_bool($required)) {
                $errors[] = self::error("{$path}.required", 'required must be a boolean.', 'invalid_value');
            }

            $options = self::options($item['options'] ?? null);

            if ($type === TemplateFieldType::Select && $options === []) {
                $errors[] = self::error("{$path}.options", 'options is required and must be non-empty for the select type.', 'required');
            }

            if ($type !== null && is_bool($required)) {
                $fields[] = new TemplateField($name, $label, $type, $required, $options);
            }
        }

        return [$fields, $errors];
    }

    /**
     * @return list<string>
     */
    private static function options(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $options = [];
        foreach ($raw as $option) {
            if (is_string($option) && trim($option) !== '') {
                $options[] = trim($option);
            }
        }

        return $options;
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
