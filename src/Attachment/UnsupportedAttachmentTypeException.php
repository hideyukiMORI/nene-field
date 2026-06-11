<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use RuntimeException;

/**
 * The detected media type is not one of the allowed work-evidence types
 * (AttachmentConstraints::ALLOWED_MIME_TYPES). Surfaces as 422.
 */
final class UnsupportedAttachmentTypeException extends RuntimeException
{
    public function __construct(
        public readonly string $detectedMimeType,
    ) {
        parent::__construct('The attachment media type is not supported.');
    }
}
