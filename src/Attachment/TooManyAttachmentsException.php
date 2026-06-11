<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use RuntimeException;

/**
 * The report already has the maximum number of attachments
 * (AttachmentConstraints::MAX_FILES_PER_REPORT). Surfaces as 413.
 */
final class TooManyAttachmentsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The report already has the maximum number of attachments.');
    }
}
