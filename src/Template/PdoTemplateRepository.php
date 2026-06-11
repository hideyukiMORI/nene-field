<?php

declare(strict_types=1);

namespace NeneField\Template;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoTemplateRepository implements TemplateRepositoryInterface
{
    private const COLUMNS = 'template_id, organization_id, name, description, fields, is_default, created_at, updated_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(string $organizationId, string $templateId): ?ReportTemplate
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM report_templates WHERE organization_id = ? AND template_id = ?',
            [$organizationId, $templateId],
        );

        return $row !== null ? self::hydrate($row) : null;
    }

    public function listByOrg(string $organizationId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM report_templates WHERE organization_id = ?
             ORDER BY is_default DESC, created_at ASC, template_id ASC',
            [$organizationId],
        );

        return array_map(static fn (array $row): ReportTemplate => self::hydrate($row), $rows);
    }

    public function insert(DatabaseQueryExecutorInterface $executor, ReportTemplate $template): void
    {
        $executor->execute(
            'INSERT INTO report_templates
                (template_id, organization_id, name, description, fields, is_default, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $template->templateId,
                $template->organizationId,
                $template->name,
                $template->description,
                self::encodeFields($template->fields),
                $template->isDefault,
                $template->createdAt,
                $template->updatedAt,
            ],
        );
    }

    public function update(DatabaseQueryExecutorInterface $executor, ReportTemplate $template): void
    {
        $executor->execute(
            'UPDATE report_templates SET name = ?, description = ?, fields = ?, is_default = ?, updated_at = ?
             WHERE organization_id = ? AND template_id = ?',
            [
                $template->name,
                $template->description,
                self::encodeFields($template->fields),
                $template->isDefault,
                $template->updatedAt,
                $template->organizationId,
                $template->templateId,
            ],
        );
    }

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $templateId): void
    {
        $executor->execute(
            'DELETE FROM report_templates WHERE organization_id = ? AND template_id = ?',
            [$organizationId, $templateId],
        );
    }

    public function clearDefault(DatabaseQueryExecutorInterface $executor, string $organizationId, ?string $exceptId, string $now): void
    {
        if ($exceptId === null) {
            $executor->execute(
                'UPDATE report_templates SET is_default = ?, updated_at = ?
                 WHERE organization_id = ? AND is_default = ?',
                [false, $now, $organizationId, true],
            );

            return;
        }

        $executor->execute(
            'UPDATE report_templates SET is_default = ?, updated_at = ?
             WHERE organization_id = ? AND is_default = ? AND template_id <> ?',
            [false, $now, $organizationId, true, $exceptId],
        );
    }

    /**
     * @param list<TemplateField> $fields
     */
    private static function encodeFields(array $fields): string
    {
        return json_encode(
            array_map(static fn (TemplateField $f): array => $f->toArray(), $fields),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydrate(array $row): ReportTemplate
    {
        return new ReportTemplate(
            templateId: (string) $row['template_id'],
            organizationId: (string) $row['organization_id'],
            name: (string) $row['name'],
            description: $row['description'] !== null ? (string) $row['description'] : null,
            fields: self::decodeFields($row['fields']),
            isDefault: (bool) $row['is_default'],
            createdAt: $row['created_at'] !== null ? (string) $row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
        );
    }

    /**
     * @return list<TemplateField>
     */
    private static function decodeFields(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        $fields = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = TemplateFieldType::tryFrom(is_string($item['type'] ?? null) ? $item['type'] : '');
            if ($type === null) {
                continue;
            }

            $fields[] = new TemplateField(
                name: is_string($item['name'] ?? null) ? $item['name'] : '',
                label: is_string($item['label'] ?? null) ? $item['label'] : '',
                type: $type,
                required: (bool) ($item['required'] ?? false),
                options: self::decodeOptions($item['options'] ?? null),
            );
        }

        return $fields;
    }

    /**
     * @return list<string>
     */
    private static function decodeOptions(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $options = [];
        foreach ($raw as $option) {
            if (is_string($option)) {
                $options[] = $option;
            }
        }

        return $options;
    }
}
