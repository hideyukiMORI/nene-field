<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * Public JSON presenter for a {@see Report} (OpenAPI `ReportResponse`). Also the
 * sanitized snapshot source for audit before/after (audit-logging.md §5).
 * `attachments` is an empty list until the attachment domain lands.
 */
final readonly class ReportResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Report $report): array
    {
        return [
            'report_id' => $report->reportId,
            'organization_id' => $report->organizationId,
            'user_id' => $report->userId,
            'template_id' => $report->templateId,
            'title' => $report->title,
            'body' => $report->body,
            'work_date' => $report->workDate,
            'status' => $report->status->value,
            'tags' => $report->tags,
            'project_code' => $report->projectCode,
            'invoice_work_order_id' => $report->invoiceWorkOrderId,
            'records_entity_id' => $report->recordsEntityId,
            'ai_summary' => $report->aiSummary,
            'ai_tags' => $report->aiTags,
            'submitted_at' => $report->submittedAt,
            'approved_at' => $report->approvedAt,
            'rejected_at' => $report->rejectedAt,
            'approver_id' => $report->approverId,
            'approver_comment' => $report->approverComment,
            'attachments' => [],
            'created_at' => $report->createdAt,
            'updated_at' => $report->updatedAt,
        ];
    }
}
