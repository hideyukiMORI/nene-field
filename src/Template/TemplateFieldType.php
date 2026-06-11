<?php

declare(strict_types=1);

namespace NeneField\Template;

/**
 * Input control types a {@see TemplateField} can declare (OpenAPI
 * `TemplateFieldDefinition.type`). `Select` additionally requires `options`.
 */
enum TemplateFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Checkbox = 'checkbox';
    case Date = 'date';
    case Select = 'select';
}
