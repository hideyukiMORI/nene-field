<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use RuntimeException;

/**
 * Attachments may only be added to or removed from a draft or rejected report
 * (ReportStatus::isEditable()). Surfaces as 409 Conflict.
 */
final class ReportNotAcceptingAttachmentsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Attachments can only be changed while the report is a draft or rejected.');
    }
}
