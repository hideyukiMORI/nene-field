<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Parses + validates the `GET /audit-events/export` query. The occurred-at range
 * is mandatory; `entity_type` optionally narrows it. Timestamps are normalized to
 * the `occurred_at` shape.
 */
final readonly class AuditEventExportRequest
{
    /**
     * @param list<array{field: string, message: string, code: string}> $errors
     */
    public function __construct(
        public string $occurredFrom,
        public string $occurredTo,
        public ?string $entityType,
        public array $errors,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function parse(array $query): self
    {
        $errors = [];

        $rawFrom = self::str($query, 'occurred_from');
        $rawTo = self::str($query, 'occurred_to');

        if ($rawFrom === '') {
            $errors[] = self::error('occurred_from', 'occurred_from is required.', 'required');
        }
        if ($rawTo === '') {
            $errors[] = self::error('occurred_to', 'occurred_to is required.', 'required');
        }

        $from = AuditTimestamp::normalize($rawFrom) ?? '';
        $to = AuditTimestamp::normalize($rawTo) ?? '';

        if ($from !== '' && $to !== '' && $from > $to) {
            $errors[] = self::error('occurred_to', 'occurred_to must not be before occurred_from.', 'invalid_range');
        }

        return new self($from, $to, self::nullable($query, 'entity_type'), $errors);
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
        $value = self::str($query, $key);

        return $value !== '' ? $value : null;
    }

    /**
     * @return array{field: string, message: string, code: string}
     */
    private static function error(string $field, string $message, string $code): array
    {
        return ['field' => $field, 'message' => $message, 'code' => $code];
    }
}
