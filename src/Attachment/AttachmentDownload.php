<?php

declare(strict_types=1);

namespace NeneField\Attachment;

/**
 * The verified bytes of an attachment together with its metadata, returned by
 * {@see DownloadAttachmentUseCase} for the handler to stream.
 */
final readonly class AttachmentDownload
{
    public function __construct(
        public ReportAttachment $attachment,
        public string $contents,
    ) {
    }
}
