<?php

declare(strict_types=1);

namespace NeneField\Attachment;

interface DeleteAttachmentUseCaseInterface
{
    /**
     * @throws AttachmentReportNotFoundException      report missing or not owned
     * @throws ReportNotAcceptingAttachmentsException report is not draft/rejected
     * @throws AttachmentNotFoundException            attachment missing
     */
    public function execute(string $organizationId, ?string $actorId, string $reportId, string $attachmentId): void;
}
