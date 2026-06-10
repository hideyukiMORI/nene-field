<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * Parses + format-validates the shared report create/update request body
 * (request-validation.md: handler-layer format validation → readonly values;
 * business invariants stay in the use case).
 */
final readonly class ReportRequest
{
    private const MAX_TITLE = 200;
    private const MAX_BODY = 10_000;

    /**
     * @param list<string>                       $tags
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public string $title,
        public string $body,
        public string $workDate,
        public array $tags,
        public ?string $templateId,
        public ?string $projectCode,
        public ?string $invoiceWorkOrderId,
        public ?string $recordsEntityId,
        public array $errors,
    ) {
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function parse(array $body): self
    {
        $errors = [];

        $title = self::stringOf($body, 'title');
        $bodyText = self::stringOf($body, 'body');
        $workDate = self::stringOf($body, 'work_date');

        if ($title === '') {
            $errors[] = self::error('title', 'title is required.', 'required');
        } elseif (mb_strlen($title) > self::MAX_TITLE) {
            $errors[] = self::error('title', 'title is too long.', 'too_long');
        }

        if ($bodyText === '') {
            $errors[] = self::error('body', 'body is required.', 'required');
        } elseif (mb_strlen($bodyText) > self::MAX_BODY) {
            $errors[] = self::error('body', 'body is too long.', 'too_long');
        }

        if ($workDate === '') {
            $errors[] = self::error('work_date', 'work_date is required.', 'required');
        } elseif (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $workDate) !== 1) {
            $errors[] = self::error('work_date', 'work_date must be an ISO date (YYYY-MM-DD).', 'invalid_format');
        }

        return new self(
            title: $title,
            body: $bodyText,
            workDate: $workDate,
            tags: self::tagsOf($body),
            templateId: self::nullableStringOf($body, 'template_id'),
            projectCode: self::nullableStringOf($body, 'project_code'),
            invoiceWorkOrderId: self::nullableStringOf($body, 'invoice_work_order_id'),
            recordsEntityId: self::nullableStringOf($body, 'records_entity_id'),
            errors: $errors,
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function stringOf(array $body, string $key): string
    {
        return is_string($body[$key] ?? null) ? trim((string) $body[$key]) : '';
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function nullableStringOf(array $body, string $key): ?string
    {
        $value = $body[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $body
     * @return list<string>
     */
    private static function tagsOf(array $body): array
    {
        $tags = $body['tags'] ?? null;

        if (!is_array($tags)) {
            return [];
        }

        $result = [];
        foreach ($tags as $tag) {
            if (is_string($tag) && $tag !== '') {
                $result[] = $tag;
            }
        }

        return $result;
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
