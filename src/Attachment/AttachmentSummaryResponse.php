<?php

declare(strict_types=1);

namespace NeneField\Attachment;

/**
 * Public JSON presenter for a {@see ReportAttachment} (OpenAPI `AttachmentSummary`).
 * The single place that decides which attachment fields are exposed; `storage_key`
 * is never included (legal-compliance NF7). Also the sanitized snapshot source
 * for audit before/after (audit-logging.md §5).
 */
final readonly class AttachmentSummaryResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(ReportAttachment $attachment): array
    {
        return [
            'attachment_id' => $attachment->attachmentId,
            'filename' => $attachment->filename,
            'mime_type' => $attachment->mimeType,
            'file_size' => $attachment->fileSize,
            'sha256' => $attachment->sha256,
            'created_at' => $attachment->createdAt,
        ];
    }
}
