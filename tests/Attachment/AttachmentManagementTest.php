<?php

declare(strict_types=1);

namespace NeneField\Tests\Attachment;

use Closure;
use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeneField\Attachment\AttachmentConstraints;
use NeneField\Attachment\AttachmentIntegrityException;
use NeneField\Attachment\AttachmentNotFoundException;
use NeneField\Attachment\AttachmentReportNotFoundException;
use NeneField\Attachment\AttachmentTooLargeException;
use NeneField\Attachment\DeleteAttachmentUseCase;
use NeneField\Attachment\DownloadAttachmentUseCase;
use NeneField\Attachment\LocalAttachmentStorage;
use NeneField\Attachment\PdoAttachmentRepository;
use NeneField\Attachment\ReportNotAcceptingAttachmentsException;
use NeneField\Attachment\TooManyAttachmentsException;
use NeneField\Attachment\UnsupportedAttachmentTypeException;
use NeneField\Attachment\UploadAttachmentInput;
use NeneField\Attachment\UploadAttachmentUseCase;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Auth\Role;
use NeneField\Report\PdoReportRepository;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end attachment management against file SQLite + a temp filesystem store.
 * Verifies the upload/download/delete lifecycle, the contract limits (count,
 * size, media type, draft-only), SHA-256 integrity, tenant/owner isolation, and
 * same-transaction `attachment.*` audit (ADR 0014).
 */
final class AttachmentManagementTest extends TestCase
{
    private const ORG = 'org-1';
    private const OWNER = 'user-1';

    // 1x1 transparent PNG.
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC';

    private string $dbPath;
    private string $storageDir;
    private PdoConnectionFactory $factory;
    private PdoReportRepository $reports;
    private PdoAttachmentRepository $attachments;
    private LocalAttachmentStorage $storage;
    private FixedClock $clock;
    /** @var Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface */
    private Closure $auditFactory;
    private PdoDatabaseTransactionManager $tx;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = tempnam(sys_get_temp_dir(), 'nf_att_db_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;
        $this->storageDir = sys_get_temp_dir() . '/nf_att_store_' . bin2hex(random_bytes(6));

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $setup = new PdoDatabaseQueryExecutor($this->factory);
        $setup->execute(AttachmentSchema::CREATE_REPORTS_TABLE);
        $setup->execute(AttachmentSchema::CREATE_ATTACHMENTS_TABLE);
        $setup->execute(AttachmentSchema::CREATE_AUDIT_TABLE);

        $this->reports = new PdoReportRepository(new PdoDatabaseQueryExecutor($this->factory));
        $this->attachments = new PdoAttachmentRepository(new PdoDatabaseQueryExecutor($this->factory));
        $this->storage = new LocalAttachmentStorage($this->storageDir);
        $this->clock = new FixedClock(new DateTimeImmutable('2026-06-11T08:00:00Z'));
        $clock = $this->clock;
        $this->auditFactory = static fn (DatabaseQueryExecutorInterface $exec): AuditRecorderInterface
            => new AuditRecorder($exec, $clock);
        $this->tx = new PdoDatabaseTransactionManager($this->factory);
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        if (is_dir($this->storageDir)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->storageDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($it as $file) {
                $path = $file->getPathname();
                $file->isDir() ? @rmdir($path) : @unlink($path);
            }
            @rmdir($this->storageDir);
        }
        parent::tearDown();
    }

    public function test_upload_download_delete_lifecycle(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);
        $png = self::png();

        $attachment = $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'photo.png', $png));
        self::assertSame('image/png', $attachment->mimeType);
        self::assertSame(hash('sha256', $png), $attachment->sha256);
        self::assertSame(strlen($png), $attachment->fileSize);
        self::assertTrue($this->storage->exists($attachment->storageKey));
        self::assertSame(1, $this->auditCount('attachment.uploaded', $attachment->attachmentId));

        // download returns the same bytes, integrity-verified
        $download = $this->downloadUseCase()->execute(self::ORG, $reportId, $attachment->attachmentId, self::OWNER, Role::Submitter);
        self::assertSame($png, $download->contents);

        // delete removes metadata, the file, and audits
        (new DeleteAttachmentUseCase($this->reports, $this->attachments, $this->storage, $this->tx, $this->auditFactory))
            ->execute(self::ORG, self::OWNER, $reportId, $attachment->attachmentId);
        self::assertNull($this->attachments->findById(self::ORG, $reportId, $attachment->attachmentId));
        self::assertFalse($this->storage->exists($attachment->storageKey));
        self::assertSame(1, $this->auditCount('attachment.deleted', $attachment->attachmentId));
    }

    public function test_rejects_unsupported_media_type(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);

        $this->expectException(UnsupportedAttachmentTypeException::class);
        $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'note.txt', 'just text'));
    }

    public function test_rejects_oversize_file(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);
        $big = str_repeat('a', AttachmentConstraints::MAX_FILE_SIZE_BYTES + 1);

        $this->expectException(AttachmentTooLargeException::class);
        $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'big.png', $big));
    }

    public function test_file_at_exact_size_limit_is_accepted(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);
        // A real PNG header (so the media type is detected) padded to exactly the
        // size limit — the boundary value that must still be accepted.
        $png = self::png();
        $atLimit = $png . str_repeat("\0", AttachmentConstraints::MAX_FILE_SIZE_BYTES - strlen($png));
        self::assertSame(AttachmentConstraints::MAX_FILE_SIZE_BYTES, strlen($atLimit));

        $attachment = $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'max.png', $atLimit));
        self::assertSame(AttachmentConstraints::MAX_FILE_SIZE_BYTES, $attachment->fileSize);
        self::assertSame('image/png', $attachment->mimeType);
    }

    public function test_fifth_file_is_accepted_then_sixth_rejected(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);

        for ($i = 0; $i < AttachmentConstraints::MAX_FILES_PER_REPORT; $i++) {
            $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, "f{$i}.png", self::png()));
        }
        self::assertSame(AttachmentConstraints::MAX_FILES_PER_REPORT, $this->attachments->countByReport(self::ORG, $reportId));

        $this->expectException(TooManyAttachmentsException::class);
        $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'sixth.png', self::png()));
    }

    public function test_rejects_more_than_five_files(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);

        for ($i = 0; $i < AttachmentConstraints::MAX_FILES_PER_REPORT; $i++) {
            $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, "f{$i}.png", self::png()));
        }

        $this->expectException(TooManyAttachmentsException::class);
        $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'sixth.png', self::png()));
    }

    public function test_cannot_attach_to_submitted_report(): void
    {
        $reportId = $this->seedReport('submitted', self::OWNER);

        $this->expectException(ReportNotAcceptingAttachmentsException::class);
        $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'photo.png', self::png()));
    }

    public function test_cannot_attach_to_report_owned_by_another_user(): void
    {
        $reportId = $this->seedReport('draft', 'someone-else');

        $this->expectException(AttachmentReportNotFoundException::class);
        $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'photo.png', self::png()));
    }

    public function test_non_owner_submitter_cannot_download(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);
        $attachment = $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'photo.png', self::png()));

        $this->expectException(AttachmentNotFoundException::class);
        $this->downloadUseCase()->execute(self::ORG, $reportId, $attachment->attachmentId, 'intruder', Role::Submitter);
    }

    public function test_approver_can_download_any_report_attachment(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);
        $attachment = $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'photo.png', self::png()));

        $download = $this->downloadUseCase()->execute(self::ORG, $reportId, $attachment->attachmentId, 'approver-1', Role::Approver);
        self::assertSame(self::png(), $download->contents);
    }

    public function test_download_detects_tampered_file(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);
        $attachment = $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'photo.png', self::png()));

        // corrupt the stored bytes behind the recorded hash
        $this->storage->write($attachment->storageKey, 'tampered');

        $this->expectException(AttachmentIntegrityException::class);
        $this->downloadUseCase()->execute(self::ORG, $reportId, $attachment->attachmentId, self::OWNER, Role::Submitter);
    }

    public function test_attachment_is_scoped_to_organization(): void
    {
        $reportId = $this->seedReport('draft', self::OWNER);
        $attachment = $this->uploadUseCase()->execute(new UploadAttachmentInput(self::ORG, self::OWNER, $reportId, 'photo.png', self::png()));

        self::assertNull($this->attachments->findById('org-2', $reportId, $attachment->attachmentId));
    }

    private function uploadUseCase(): UploadAttachmentUseCase
    {
        return new UploadAttachmentUseCase($this->reports, $this->attachments, $this->storage, $this->tx, $this->auditFactory, $this->clock);
    }

    private function downloadUseCase(): DownloadAttachmentUseCase
    {
        return new DownloadAttachmentUseCase($this->reports, $this->attachments, $this->storage);
    }

    private static function png(): string
    {
        $bytes = base64_decode(self::PNG, true);
        self::assertIsString($bytes);

        return $bytes;
    }

    private function seedReport(string $status, string $owner): string
    {
        $reportId = 'report-' . bin2hex(random_bytes(6));
        $write = new PdoDatabaseQueryExecutor($this->factory);
        $write->execute(
            'INSERT INTO reports (report_id, organization_id, user_id, title, body, work_date, status, tags, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$reportId, self::ORG, $owner, 'T', 'B', '2026-06-11', $status, '[]', '2026-06-11 00:00:00', '2026-06-11 00:00:00'],
        );

        return $reportId;
    }

    private function auditCount(string $eventName, string $entityId): int
    {
        $read = new PdoDatabaseQueryExecutor($this->factory);
        $row = $read->fetchOne(
            'SELECT COUNT(*) AS c FROM audit_events WHERE event_name = ? AND entity_id = ?',
            [$eventName, $entityId],
        );

        return $row !== null ? (int) $row['c'] : -1;
    }
}
