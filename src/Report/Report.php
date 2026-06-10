<?php

declare(strict_types=1);

namespace NeneField\Report;

/**
 * A daily report (domain-model.md). Tenant-scoped by `organizationId`; owned by
 * the submitter `userId`. Immutable value object — lifecycle transitions produce
 * a new instance via the `with*` helpers.
 */
final readonly class Report
{
    /**
     * @param list<string>      $tags
     * @param list<string>|null $aiTags
     */
    public function __construct(
        public string $reportId,
        public string $organizationId,
        public string $userId,
        public string $title,
        public string $body,
        public string $workDate,
        public ReportStatus $status,
        public array $tags = [],
        public ?string $templateId = null,
        public ?string $projectCode = null,
        public ?string $invoiceWorkOrderId = null,
        public ?string $recordsEntityId = null,
        public ?string $aiSummary = null,
        public ?array $aiTags = null,
        public ?string $submittedAt = null,
        public ?string $approvedAt = null,
        public ?string $rejectedAt = null,
        public ?string $approverId = null,
        public ?string $approverComment = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    /**
     * Returns a copy with edited content (status preserved).
     *
     * @param list<string> $tags
     */
    public function withEditedContent(
        string $title,
        string $body,
        string $workDate,
        array $tags,
        ?string $templateId,
        ?string $projectCode,
        ?string $invoiceWorkOrderId,
        ?string $recordsEntityId,
        string $updatedAt,
    ): self {
        return new self(
            reportId: $this->reportId,
            organizationId: $this->organizationId,
            userId: $this->userId,
            title: $title,
            body: $body,
            workDate: $workDate,
            status: $this->status,
            tags: $tags,
            templateId: $templateId,
            projectCode: $projectCode,
            invoiceWorkOrderId: $invoiceWorkOrderId,
            recordsEntityId: $recordsEntityId,
            aiSummary: $this->aiSummary,
            aiTags: $this->aiTags,
            submittedAt: $this->submittedAt,
            approvedAt: $this->approvedAt,
            rejectedAt: $this->rejectedAt,
            approverId: $this->approverId,
            approverComment: $this->approverComment,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
        );
    }

    /** Returns a copy transitioned to `submitted`. */
    public function withSubmitted(string $submittedAt): self
    {
        return new self(
            reportId: $this->reportId,
            organizationId: $this->organizationId,
            userId: $this->userId,
            title: $this->title,
            body: $this->body,
            workDate: $this->workDate,
            status: ReportStatus::Submitted,
            tags: $this->tags,
            templateId: $this->templateId,
            projectCode: $this->projectCode,
            invoiceWorkOrderId: $this->invoiceWorkOrderId,
            recordsEntityId: $this->recordsEntityId,
            aiSummary: $this->aiSummary,
            aiTags: $this->aiTags,
            submittedAt: $submittedAt,
            approvedAt: $this->approvedAt,
            rejectedAt: $this->rejectedAt,
            approverId: $this->approverId,
            approverComment: $this->approverComment,
            createdAt: $this->createdAt,
            updatedAt: $submittedAt,
        );
    }
}
