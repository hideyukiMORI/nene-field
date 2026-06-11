<?php

declare(strict_types=1);

namespace NeneField\Tests\Template;

use NeneField\Template\TemplateFieldsRequest;
use NeneField\Template\TemplateFieldType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Boundary-value validation for the `fields[]` parser shared by template create
 * and update. Per-field name (100) and label (200) limits are checked at the
 * edge; `select` requires non-empty options; the type enum rejects unknowns.
 */
final class TemplateFieldsRequestTest extends TestCase
{
    public function test_fields_must_be_a_list(): void
    {
        [$fields, $errors] = TemplateFieldsRequest::parse(['fields' => ['name' => 'x']]);
        self::assertSame([], $fields);
        self::assertSame('fields', $errors[0]['field']);
        self::assertSame('invalid_type', $errors[0]['code']);
    }

    public function test_missing_fields_key_is_a_list_error(): void
    {
        [, $errors] = TemplateFieldsRequest::parse([]);
        self::assertSame('invalid_type', self::codeFor($errors, 'fields'));
    }

    #[DataProvider('validTypes')]
    public function test_each_field_type_is_accepted(string $type): void
    {
        $body = ['fields' => [self::field(['type' => $type, 'options' => ['o']])]];
        [$fields, $errors] = TemplateFieldsRequest::parse($body);

        self::assertSame([], $errors);
        self::assertCount(1, $fields);
        self::assertSame(TemplateFieldType::from($type), $fields[0]->type);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validTypes(): iterable
    {
        yield 'text' => ['text'];
        yield 'textarea' => ['textarea'];
        yield 'number' => ['number'];
        yield 'checkbox' => ['checkbox'];
        yield 'date' => ['date'];
        yield 'select' => ['select'];
    }

    public function test_unknown_type_is_rejected(): void
    {
        [, $errors] = TemplateFieldsRequest::parse(['fields' => [self::field(['type' => 'rating'])]]);
        self::assertSame('invalid_value', self::codeFor($errors, 'fields.0.type'));
    }

    public function test_name_at_max_ok_over_rejected(): void
    {
        self::assertNull(self::codeFor(self::parseOne(['name' => str_repeat('a', 100)]), 'fields.0.name'));
        self::assertSame('too_long', self::codeFor(self::parseOne(['name' => str_repeat('a', 101)]), 'fields.0.name'));
    }

    public function test_label_at_max_ok_over_rejected(): void
    {
        self::assertNull(self::codeFor(self::parseOne(['label' => str_repeat('a', 200)]), 'fields.0.label'));
        self::assertSame('too_long', self::codeFor(self::parseOne(['label' => str_repeat('a', 201)]), 'fields.0.label'));
    }

    public function test_name_and_label_required(): void
    {
        self::assertSame('required', self::codeFor(self::parseOne(['name' => '']), 'fields.0.name'));
        self::assertSame('required', self::codeFor(self::parseOne(['label' => '   ']), 'fields.0.label'));
    }

    public function test_required_flag_must_be_boolean(): void
    {
        self::assertSame('invalid_value', self::codeFor(self::parseOne(['required' => 'yes']), 'fields.0.required'));
    }

    public function test_select_requires_non_empty_options(): void
    {
        self::assertSame('required', self::codeFor(self::parseOne(['type' => 'select', 'options' => []]), 'fields.0.options'));
        self::assertSame('required', self::codeFor(self::parseOne(['type' => 'select']), 'fields.0.options'));

        [$fields, $errors] = TemplateFieldsRequest::parse(['fields' => [self::field(['type' => 'select', 'options' => ['a', 'b']])]]);
        self::assertSame([], $errors);
        self::assertSame(['a', 'b'], $fields[0]->options);
    }

    public function test_options_filters_blank_and_non_string(): void
    {
        [$fields] = TemplateFieldsRequest::parse(['fields' => [self::field(['type' => 'select', 'options' => ['a', '', 'b', 3, '  c  ']])]]);
        self::assertSame(['a', 'b', 'c'], $fields[0]->options);
    }

    public function test_non_object_field_is_rejected(): void
    {
        [, $errors] = TemplateFieldsRequest::parse(['fields' => ['not-an-object']]);
        self::assertSame('invalid_type', self::codeFor($errors, 'fields.0'));
    }

    public function test_second_field_path_is_indexed(): void
    {
        [, $errors] = TemplateFieldsRequest::parse(['fields' => [self::field([]), self::field(['name' => ''])]]);
        self::assertSame('required', self::codeFor($errors, 'fields.1.name'));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return list<array{field: string, message: string, code: string}>
     */
    private static function parseOne(array $overrides): array
    {
        [, $errors] = TemplateFieldsRequest::parse(['fields' => [self::field($overrides)]]);

        return $errors;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function field(array $overrides): array
    {
        return array_merge(['name' => 'f', 'label' => 'Field', 'type' => 'text', 'required' => false], $overrides);
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
