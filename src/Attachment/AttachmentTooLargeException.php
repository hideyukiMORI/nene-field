<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use RuntimeException;

/**
 * The uploaded file exceeds AttachmentConstraints::MAX_FILE_SIZE_BYTES.
 * Surfaces as 413 Payload Too Large.
 */
final class AttachmentTooLargeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The attachment exceeds the maximum file size.');
    }
}
