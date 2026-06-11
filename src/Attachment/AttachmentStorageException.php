<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use RuntimeException;

/**
 * A storage-layer failure (missing object, unreadable/unwritable path). Surfaces
 * as a 500-class problem; never leaks the underlying filesystem path (NF7).
 */
final class AttachmentStorageException extends RuntimeException
{
}
