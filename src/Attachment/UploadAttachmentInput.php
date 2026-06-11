<?php

declare(strict_types=1);

namespace NeneField\Attachment;

final readonly class UploadAttachmentInput
{
    public function __construct(
        public string $organizationId,
        public ?string $actorId,
        public string $reportId,
        public string $filename,
        public string $contents,
    ) {
    }
}
