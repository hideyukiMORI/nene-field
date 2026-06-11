<?php

declare(strict_types=1);

namespace NeneField\Attachment;

interface UploadAttachmentUseCaseInterface
{
    /**
     * @throws AttachmentReportNotFoundException        report missing or not owned
     * @throws ReportNotAcceptingAttachmentsException   report is not draft/rejected
     * @throws TooManyAttachmentsException              per-report file count reached
     * @throws AttachmentTooLargeException              file exceeds the size limit
     * @throws UnsupportedAttachmentTypeException       detected media type not allowed
     */
    public function execute(UploadAttachmentInput $input): ReportAttachment;
}
