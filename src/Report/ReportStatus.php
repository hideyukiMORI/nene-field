<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * Report lifecycle states (terms.md §2). draft → submitted → approved / rejected.
 */
enum ReportStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Editable by the submitter (content changes / delete). */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }

    /** Can be submitted for approval. */
    public function isSubmittable(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }
}
