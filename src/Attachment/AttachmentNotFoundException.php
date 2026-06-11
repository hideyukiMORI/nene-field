<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use RuntimeException;

/**
 * The attachment does not exist for the given report in the caller's
 * organization. Surfaces as 404.
 */
final class AttachmentNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The attachment was not found.');
    }
}
