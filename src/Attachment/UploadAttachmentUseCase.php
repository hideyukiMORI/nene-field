<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Report\ReportRepositoryInterface;
use NeneField\Support\Uuid;
use Throwable;

/**
 * Stores an uploaded file and records its metadata. The media type is detected
 * from the bytes (never trusted from the client), the SHA-256 is computed for
 * integrity (NF11), and the binary is written under an opaque storage key whose
 * path is never exposed (NF7). The metadata row + `attachment.uploaded` audit
 * commit in one transaction; the on-disk file is removed if that transaction
 * fails, so storage and metadata cannot drift.
 */
final readonly class UploadAttachmentUseCase implements UploadAttachmentUseCaseInterface
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
        private ClockInterface $clock,
    ) {
    }

    public function execute(UploadAttachmentInput $input): ReportAttachment
    {
        $report = $this->reports->findById($input->organizationId, $input->reportId);

        if ($report === null || $report->userId !== $input->actorId) {
            throw new AttachmentReportNotFoundException();
        }

        if (!$report->status->isEditable()) {
            throw new ReportNotAcceptingAttachmentsException();
        }

        if ($this->attachments->countByReport($input->organizationId, $input->reportId) >= AttachmentConstraints::MAX_FILES_PER_REPORT) {
            throw new TooManyAttachmentsException();
        }

        $size = strlen($input->contents);

        if ($size > AttachmentConstraints::MAX_FILE_SIZE_BYTES) {
            throw new AttachmentTooLargeException();
        }

        $mimeType = self::detectMimeType($input->contents);

        if (!AttachmentConstraints::isAllowedMimeType($mimeType)) {
            throw new UnsupportedAttachmentTypeException($mimeType);
        }

        $attachmentId = Uuid::v4();
        $storageKey = sprintf('att/%s/%s/%s', $input->organizationId, $input->reportId, $attachmentId);

        $attachment = new ReportAttachment(
            attachmentId: $attachmentId,
            reportId: $input->reportId,
            organizationId: $input->organizationId,
            uploadedBy: $input->actorId,
            filename: self::sanitizeFilename($input->filename, $mimeType),
            mimeType: $mimeType,
            fileSize: $size,
            sha256: hash('sha256', $input->contents),
            storageKey: $storageKey,
            createdAt: $this->clock->now()->format('Y-m-d H:i:s'),
        );

        $this->storage->write($storageKey, $input->contents);

        try {
            $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($attachment, $input): void {
                $this->attachments->insert($exec, $attachment);
                ($this->auditFactory)($exec)->record(
                    $input->actorId,
                    $input->organizationId,
                    'attachment.uploaded',
                    'ReportAttachment',
                    $attachment->attachmentId,
                    null,
                    AttachmentSummaryResponse::toArray($attachment),
                );
            });
        } catch (Throwable $e) {
            $this->storage->delete($storageKey);

            throw $e;
        }

        return $attachment;
    }

    private static function detectMimeType(string $contents): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return 'application/octet-stream';
        }

        $mime = finfo_buffer($finfo, $contents);
        finfo_close($finfo);

        return is_string($mime) ? $mime : 'application/octet-stream';
    }

    /**
     * Keeps only the base name and a conservative character set, falling back to
     * a typed default so the stored display name is always safe and non-empty.
     */
    private static function sanitizeFilename(string $filename, string $mimeType): string
    {
        $base = basename(str_replace('\\', '/', $filename));
        $base = preg_replace('/[\x00-\x1F\x7F]+/u', '', $base) ?? '';
        $base = trim($base);

        if ($base === '' || $base === '.' || $base === '..') {
            return 'attachment.' . AttachmentConstraints::ALLOWED_MIME_TYPES[$mimeType];
        }

        return mb_substr($base, 0, 255);
    }
}
