<?php

declare(strict_types=1);

namespace NeneField\Report;

use RuntimeException;

/**
 * An approve/reject was attempted on a report that is not in the `submitted`
 * state (terms.md §7 `report-not-in-submitted-state`). Surfaces as 409.
 */
final class ReportNotInSubmittedStateException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The report is not awaiting approval.');
    }
}
