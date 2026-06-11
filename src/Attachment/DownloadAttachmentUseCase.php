<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use NeneField\Auth\Role;
use NeneField\Report\ReportRepositoryInterface;

/**
 * Reads an attachment's bytes for an authenticated caller who can see the report
 * (a submitter only sees their own reports; approver/admin see all). The bytes
 * are re-hashed and compared with the stored SHA-256 before returning, so a
 * corrupt or tampered file is never served (legal-compliance NF11).
 */
final readonly class DownloadAttachmentUseCase implements DownloadAttachmentUseCaseInterface
{
    public function __construct(
        private ReportRepositoryInterface $reports,
        private AttachmentRepositoryInterface $attachments,
        private AttachmentStorageInterface $storage,
    ) {
    }

    public function execute(string $organizationId, string $reportId, string $attachmentId, ?string $actorId, Role $role): AttachmentDownload
    {
        $report = $this->reports->findById($organizationId, $reportId);

        // Same visibility as GET /reports/{id}: non-owner submitters get 404.
        if ($report === null || (!$role->canApprove() && $report->userId !== $actorId)) {
            throw new AttachmentNotFoundException();
        }

        $attachment = $this->attachments->findById($organizationId, $reportId, $attachmentId);

        if ($attachment === null) {
            throw new AttachmentNotFoundException();
        }

        $contents = $this->storage->read($attachment->storageKey);

        if (!hash_equals($attachment->sha256, hash('sha256', $contents))) {
            throw new AttachmentIntegrityException();
        }

        return new AttachmentDownload($attachment, $contents);
    }
}
