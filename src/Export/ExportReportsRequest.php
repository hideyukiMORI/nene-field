<?php

declare(strict_types=1);

namespace NeneField\Export;

use NeneField\Report\ReportStatus;

/**
 * Parses + format-validates the `GET /export/csv` query string. The work-date
 * range is mandatory; `status` defaults to `[approved]` when omitted (OpenAPI).
 */
final readonly class ExportReportsRequest
{
    /**
     * @param list<ReportStatus>                                         $statuses
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public string $workDateFrom,
        public string $workDateTo,
        public ?string $userId,
        public ?string $projectCode,
        public array $statuses,
        public array $errors,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function parse(array $query): self
    {
        $errors = [];

        $from = self::str($query, 'work_date_from');
        $to = self::str($query, 'work_date_to');

        self::requireDate($from, 'work_date_from', $errors);
        self::requireDate($to, 'work_date_to', $errors);

        if ($from !== '' && $to !== '' && $from > $to) {
            $errors[] = self::error('work_date_to', 'work_date_to must not be before work_date_from.', 'invalid_range');
        }

        return new self(
            workDateFrom: $from,
            workDateTo: $to,
            userId: self::nullable($query, 'user_id'),
            projectCode: self::nullable($query, 'project_code'),
            statuses: self::statuses($query['status'] ?? null),
            errors: $errors,
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function str(array $query, string $key): string
    {
        return is_string($query[$key] ?? null) ? trim((string) $query[$key]) : '';
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function nullable(array $query, string $key): ?string
    {
        $value = $query[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    private static function requireDate(string $value, string $field, array &$errors): void
    {
        if ($value === '') {
            $errors[] = self::error($field, "{$field} is required.", 'required');
        } elseif (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value) !== 1) {
            $errors[] = self::error($field, "{$field} must be an ISO date (YYYY-MM-DD).", 'invalid_format');
        }
    }

    /**
     * @return list<ReportStatus>
     */
    private static function statuses(mixed $raw): array
    {
        $values = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? [$raw] : []);
        $result = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $status = ReportStatus::tryFrom($value);
                if ($status !== null) {
                    $result[] = $status;
                }
            }
        }

        return $result === [] ? [ReportStatus::Approved] : $result;
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
