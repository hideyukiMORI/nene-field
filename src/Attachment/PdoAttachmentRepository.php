<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoAttachmentRepository implements AttachmentRepositoryInterface
{
    private const COLUMNS = 'attachment_id, report_id, organization_id, uploaded_by, filename, '
        . 'mime_type, file_size, sha256, storage_key, created_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(string $organizationId, string $reportId, string $attachmentId): ?ReportAttachment
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM report_attachments
             WHERE organization_id = ? AND report_id = ? AND attachment_id = ?',
            [$organizationId, $reportId, $attachmentId],
        );

        return $row !== null ? self::hydrate($row) : null;
    }

    public function listByReport(string $organizationId, string $reportId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM report_attachments
             WHERE organization_id = ? AND report_id = ?
             ORDER BY created_at ASC, attachment_id ASC',
            [$organizationId, $reportId],
        );

        return array_map(static fn (array $row): ReportAttachment => self::hydrate($row), $rows);
    }

    public function countByReport(string $organizationId, string $reportId): int
    {
        $row = $this->query->fetchOne(
            'SELECT COUNT(*) AS c FROM report_attachments WHERE organization_id = ? AND report_id = ?',
            [$organizationId, $reportId],
        );

        return $row !== null ? (int) $row['c'] : 0;
    }

    public function insert(DatabaseQueryExecutorInterface $executor, ReportAttachment $attachment): void
    {
        $executor->execute(
            'INSERT INTO report_attachments
                (attachment_id, report_id, organization_id, uploaded_by, filename, mime_type,
                 file_size, sha256, storage_key, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $attachment->attachmentId,
                $attachment->reportId,
                $attachment->organizationId,
                $attachment->uploadedBy,
                $attachment->filename,
                $attachment->mimeType,
                $attachment->fileSize,
                $attachment->sha256,
                $attachment->storageKey,
                $attachment->createdAt,
            ],
        );
    }

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $reportId, string $attachmentId): void
    {
        $executor->execute(
            'DELETE FROM report_attachments WHERE organization_id = ? AND report_id = ? AND attachment_id = ?',
            [$organizationId, $reportId, $attachmentId],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydrate(array $row): ReportAttachment
    {
        return new ReportAttachment(
            attachmentId: (string) $row['attachment_id'],
            reportId: (string) $row['report_id'],
            organizationId: (string) $row['organization_id'],
            uploadedBy: $row['uploaded_by'] !== null ? (string) $row['uploaded_by'] : null,
            filename: (string) $row['filename'],
            mimeType: (string) $row['mime_type'],
            fileSize: (int) $row['file_size'],
            sha256: (string) $row['sha256'],
            storageKey: (string) $row['storage_key'],
            createdAt: $row['created_at'] !== null ? (string) $row['created_at'] : null,
        );
    }
}
