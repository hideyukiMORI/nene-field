<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Report\ReportRepositoryInterface;

/**
 * Removes an attachment from a draft/rejected report owned by the caller. The
 * metadata row + `attachment.deleted` audit commit in one transaction; the
 * on-disk file is removed best-effort after the commit (an orphaned blob is
 * harmless, a missing metadata row with a live file is not).
 */
final readonly class DeleteAttachmentUseCase implements DeleteAttachmentUseCaseInterface
{
    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private ReportRepositoryInterface $reports,
        private AttachmentRepositoryInterface $attachments,
        private AttachmentStorageInterface $storage,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
    ) {
    }

    public function execute(string $organizationId, ?string $actorId, string $reportId, string $attachmentId): void
    {
        $report = $this->reports->findById($organizationId, $reportId);

        if ($report === null || $report->userId !== $actorId) {
            throw new AttachmentReportNotFoundException();
        }

        if (!$report->status->isEditable()) {
            throw new ReportNotAcceptingAttachmentsException();
        }

        $attachment = $this->attachments->findById($organizationId, $reportId, $attachmentId);

        if ($attachment === null) {
            throw new AttachmentNotFoundException();
        }

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($attachment, $organizationId, $actorId, $reportId, $attachmentId): void {
            $this->attachments->delete($exec, $organizationId, $reportId, $attachmentId);
            ($this->auditFactory)($exec)->record(
                $actorId,
                $organizationId,
                'attachment.deleted',
                'ReportAttachment',
                $attachmentId,
                AttachmentSummaryResponse::toArray($attachment),
                null,
            );
        });

        $this->storage->delete($attachment->storageKey);
    }
}
