<?php

declare(strict_types=1);

namespace NeneField\Attachment;

/**
 * Metadata for a file attached to a report (terms.md §1: `ReportAttachment`).
 * The binary lives in {@see AttachmentStorageInterface} under `storageKey`; this
 * record holds only metadata. `sha256` is the integrity check (NF11); `storageKey`
 * is internal and never presented in an API response (NF7).
 */
final readonly class ReportAttachment
{
    public function __construct(
        public string $attachmentId,
        public string $reportId,
        public string $organizationId,
        public ?string $uploadedBy,
        public string $filename,
        public string $mimeType,
        public int $fileSize,
        public string $sha256,
        public string $storageKey,
        public ?string $createdAt = null,
    ) {
    }
}
