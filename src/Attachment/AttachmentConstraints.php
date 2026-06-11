<?php

declare(strict_types=1);

namespace NeneField\Attachment;

/**
 * Upload limits fixed by the API contract (OpenAPI / terms.md §7
 * `payload-too-large`): at most 5 files per report, 5 MB each, and only the
 * three work-evidence media types.
 */
final class AttachmentConstraints
{
    public const MAX_FILES_PER_REPORT = 5;
    public const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024;

    /** @var array<string, string> mime type => canonical file extension */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    public static function isAllowedMimeType(string $mimeType): bool
    {
        return array_key_exists($mimeType, self::ALLOWED_MIME_TYPES);
    }
}
