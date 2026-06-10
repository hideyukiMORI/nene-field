<?php

declare(strict_types=1);

namespace NeneField\Report;

use RuntimeException;

/**
 * A content edit, delete, or submit was attempted on a report whose lifecycle
 * state does not allow it (e.g. `submitted` / `approved`). Surfaces as 409.
 */
final class ReportNotEditableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The report cannot be modified in its current state.');
    }
}
