<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use RuntimeException;

/**
 * The target report does not exist in the caller's organization, or the caller
 * is not its owner — both surface as 404 so existence is not disclosed.
 */
final class AttachmentReportNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The report was not found.');
    }
}
