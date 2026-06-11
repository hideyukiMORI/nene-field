<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use RuntimeException;

/**
 * The stored bytes do not match the recorded SHA-256 (legal-compliance NF11):
 * the file is corrupt or was tampered with. Surfaces as a 500-class problem.
 */
final class AttachmentIntegrityException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The attachment failed its integrity check.');
    }
}
