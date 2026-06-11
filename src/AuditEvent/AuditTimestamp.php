<?php

declare(strict_types=1);

namespace NeneField\AuditEvent;

/**
 * Normalizes a client-supplied timestamp filter to the `Y-m-d H:i:s` shape used
 * by the `occurred_at` column, so an ISO 8601 value (`2026-06-01T09:00:00Z`)
 * compares correctly against the stored value. A bare date (`2026-06-01`) is
 * left as-is and still compares correctly as a prefix.
 */
final class AuditTimestamp
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Drop a trailing zone designator and swap the ISO date/time separator.
        $value = preg_replace('/(Z|[+-]\d{2}:?\d{2})$/', '', $value) ?? $value;
        $value = str_replace('T', ' ', $value);

        return trim($value);
    }
}
