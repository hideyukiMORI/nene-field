<?php

declare(strict_types=1);

namespace NeneField\Tests\Template;

use NeneField\Template\CreateTemplateRequest;
use NeneField\Template\TemplateFieldType;
use NeneField\Template\UpdateTemplateRequest;
use PHPUnit\Framework\TestCase;

/**
 * Format-validation rules for the template request bodies (handler-layer
 * validation → readonly values; business invariants stay in the use cases).
 */
final class TemplateRequestTest extends TestCase
{
    public function test_valid_create_parses_fields(): void
    {
        $request = CreateTemplateRequest::parse([
            'name' => '日報',
            'description' => 'desc',
            'is_default' => true,
            'fields' => [
                ['name' => 'summary', 'label' => '作業内容', 'type' => 'textarea', 'required' => true],
                ['name' => 'weather', 'label' => '天候', 'type' => 'select', 'required' => false, 'options' => ['晴れ', '雨']],
            ],
        ]);

        self::assertSame([], $request->errors);
        self::assertTrue($request->isDefault);
        self::assertCount(2, $request->fields);
        self::assertSame(TemplateFieldType::Select, $request->fields[1]->type);
        self::assertSame(['晴れ', '雨'], $request->fields[1]->options);
    }

    public function test_create_requires_name_and_fields(): void
    {
        $request = CreateTemplateRequest::parse([]);

        $codes = array_map(static fn (array $e): string => $e['field'], $request->errors);
        self::assertContains('name', $codes);
        self::assertContains('fields', $codes);
    }

    public function test_select_field_requires_options(): void
    {
        $request = CreateTemplateRequest::parse([
            'name' => 'T',
            'fields' => [
                ['name' => 'weather', 'label' => '天候', 'type' => 'select', 'required' => false],
            ],
        ]);

        $fields = array_map(static fn (array $e): string => $e['field'], $request->errors);
        self::assertContains('fields.0.options', $fields);
    }

    public function test_invalid_field_type_is_rejected(): void
    {
        $request = CreateTemplateRequest::parse([
            'name' => 'T',
            'fields' => [
                ['name' => 'x', 'label' => 'X', 'type' => 'bogus', 'required' => true],
            ],
        ]);

        $fields = array_map(static fn (array $e): string => $e['field'], $request->errors);
        self::assertContains('fields.0.type', $fields);
    }

    public function test_update_is_partial_and_tracks_provided_fields(): void
    {
        $request = UpdateTemplateRequest::parse(['name' => '改名']);

        self::assertSame([], $request->errors);
        self::assertSame('改名', $request->name);
        self::assertNull($request->fields, 'fields absent → keep existing');
        self::assertNull($request->isDefault);
        self::assertFalse($request->descriptionProvided);
    }
}
