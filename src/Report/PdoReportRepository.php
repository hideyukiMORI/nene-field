<?php

declare(strict_types=1);

namespace NeneField\Report;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoReportRepository implements ReportRepositoryInterface
{
    private const COLUMNS = 'report_id, organization_id, user_id, template_id, title, body, work_date, '
        . 'status, tags, project_code, invoice_work_order_id, records_entity_id, ai_summary, ai_tags, '
        . 'submitted_at, approved_at, rejected_at, approver_id, approver_comment, created_at, updated_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(string $organizationId, string $reportId): ?Report
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM reports WHERE organization_id = ? AND report_id = ?',
            [$organizationId, $reportId],
        );

        return $row !== null ? self::hydrate($row) : null;
    }

    public function insert(DatabaseQueryExecutorInterface $executor, Report $report): void
    {
        $executor->execute(
            'INSERT INTO reports
                (report_id, organization_id, user_id, template_id, title, body, work_date, status, tags,
                 project_code, invoice_work_order_id, records_entity_id, ai_summary, ai_tags,
                 submitted_at, approved_at, rejected_at, approver_id, approver_comment, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            self::params($report),
        );
    }

    public function update(DatabaseQueryExecutorInterface $executor, Report $report): void
    {
        $executor->execute(
            'UPDATE reports SET
                template_id = ?, title = ?, body = ?, work_date = ?, status = ?, tags = ?,
                project_code = ?, invoice_work_order_id = ?, records_entity_id = ?, ai_summary = ?, ai_tags = ?,
                submitted_at = ?, approved_at = ?, rejected_at = ?, approver_id = ?, approver_comment = ?, updated_at = ?
             WHERE organization_id = ? AND report_id = ?',
            [
                $report->templateId,
                $report->title,
                $report->body,
                $report->workDate,
                $report->status->value,
                self::encode($report->tags),
                $report->projectCode,
                $report->invoiceWorkOrderId,
                $report->recordsEntityId,
                $report->aiSummary,
                $report->aiTags !== null ? self::encode($report->aiTags) : null,
                $report->submittedAt,
                $report->approvedAt,
                $report->rejectedAt,
                $report->approverId,
                $report->approverComment,
                $report->updatedAt,
                $report->organizationId,
                $report->reportId,
            ],
        );
    }

    public function delete(DatabaseQueryExecutorInterface $executor, string $organizationId, string $reportId): void
    {
        $executor->execute(
            'DELETE FROM reports WHERE organization_id = ? AND report_id = ?',
            [$organizationId, $reportId],
        );
    }

    /**
     * @return list<mixed>
     */
    private static function params(Report $report): array
    {
        return [
            $report->reportId,
            $report->organizationId,
            $report->userId,
            $report->templateId,
            $report->title,
            $report->body,
            $report->workDate,
            $report->status->value,
            self::encode($report->tags),
            $report->projectCode,
            $report->invoiceWorkOrderId,
            $report->recordsEntityId,
            $report->aiSummary,
            $report->aiTags !== null ? self::encode($report->aiTags) : null,
            $report->submittedAt,
            $report->approvedAt,
            $report->rejectedAt,
            $report->approverId,
            $report->approverComment,
            $report->createdAt,
            $report->updatedAt,
        ];
    }

    /**
     * @param list<string> $values
     */
    private static function encode(array $values): string
    {
        return json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydrate(array $row): Report
    {
        return new Report(
            reportId: (string) $row['report_id'],
            organizationId: (string) $row['organization_id'],
            userId: (string) $row['user_id'],
            title: (string) $row['title'],
            body: (string) $row['body'],
            workDate: (string) $row['work_date'],
            status: ReportStatus::from((string) $row['status']),
            tags: self::decode($row['tags']),
            templateId: self::nullableString($row['template_id']),
            projectCode: self::nullableString($row['project_code']),
            invoiceWorkOrderId: self::nullableString($row['invoice_work_order_id']),
            recordsEntityId: self::nullableString($row['records_entity_id']),
            aiSummary: self::nullableString($row['ai_summary']),
            aiTags: $row['ai_tags'] !== null ? self::decode($row['ai_tags']) : null,
            submittedAt: self::nullableString($row['submitted_at']),
            approvedAt: self::nullableString($row['approved_at']),
            rejectedAt: self::nullableString($row['rejected_at']),
            approverId: self::nullableString($row['approver_id']),
            approverComment: self::nullableString($row['approver_comment']),
            createdAt: self::nullableString($row['created_at']),
            updatedAt: self::nullableString($row['updated_at']),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value !== null ? (string) $value : null;
    }

    /**
     * @return list<string>
     */
    private static function decode(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $v): string => (string) $v, $decoded));
    }
}
