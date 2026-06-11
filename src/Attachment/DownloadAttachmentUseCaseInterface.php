<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use NeneField\Auth\Role;

interface DownloadAttachmentUseCaseInterface
{
    /**
     * @throws AttachmentNotFoundException  attachment/report missing or not visible
     * @throws AttachmentStorageException   the stored object cannot be read
     * @throws AttachmentIntegrityException stored bytes fail the SHA-256 check
     */
    public function execute(string $organizationId, string $reportId, string $attachmentId, ?string $actorId, Role $role): AttachmentDownload;
}
