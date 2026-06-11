<?php

declare(strict_types=1);

namespace NeneField\Template;

/**
 * One field definition inside a {@see ReportTemplate} (OpenAPI
 * `TemplateFieldDefinition`). `options` is only meaningful for the `select` type.
 */
final readonly class TemplateField
{
    /**
     * @param list<string> $options
     */
    public function __construct(
        public string $name,
        public string $label,
        public TemplateFieldType $type,
        public bool $required,
        public array $options = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'required' => $this->required,
        ];

        if ($this->type === TemplateFieldType::Select) {
            $data['options'] = $this->options;
        }

        return $data;
    }
}
