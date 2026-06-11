<?php

declare(strict_types=1);

namespace NeneField\Organization;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoOrganizationRepository implements OrganizationRepositoryInterface
{
    private const COLUMNS = 'organization_id, name, slug, custom_domain, is_active, '
        . 'ai_summary_enabled, notification_email, webhook_url, created_at, updated_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(string $organizationId): ?Organization
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM organizations WHERE organization_id = ?',
            [$organizationId],
        );

        return $row !== null ? self::hydrate($row) : null;
    }

    public function findBySlug(string $slug): ?Organization
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM organizations WHERE slug = ?',
            [$slug],
        );

        return $row !== null ? self::hydrate($row) : null;
    }

    public function findByCustomDomain(string $customDomain): ?Organization
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM organizations WHERE custom_domain = ?',
            [$customDomain],
        );

        return $row !== null ? self::hydrate($row) : null;
    }

    public function listAll(int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM organizations
             ORDER BY created_at ASC, organization_id ASC LIMIT ? OFFSET ?',
            [$limit, $offset],
        );

        return array_map(static fn (array $row): Organization => self::hydrate($row), $rows);
    }

    public function countAll(): int
    {
        $row = $this->query->fetchOne('SELECT COUNT(*) AS c FROM organizations', []);

        return $row !== null ? (int) $row['c'] : 0;
    }

    public function insert(DatabaseQueryExecutorInterface $executor, Organization $organization): void
    {
        $executor->execute(
            'INSERT INTO organizations
                (organization_id, name, slug, custom_domain, is_active, ai_summary_enabled,
                 notification_email, webhook_url, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $organization->organizationId,
                $organization->name,
                $organization->slug,
                $organization->customDomain,
                $organization->isActive,
                $organization->aiSummaryEnabled,
                $organization->notificationEmail,
                $organization->webhookUrl,
                $organization->createdAt,
                $organization->updatedAt,
            ],
        );
    }

    public function update(DatabaseQueryExecutorInterface $executor, Organization $organization): void
    {
        // The secret AI columns (ai_api_url / ai_api_key) are intentionally left
        // out of this statement so settings updates never touch them.
        $executor->execute(
            'UPDATE organizations SET
                name = ?, slug = ?, custom_domain = ?, is_active = ?, ai_summary_enabled = ?,
                notification_email = ?, webhook_url = ?, updated_at = ?
             WHERE organization_id = ?',
            [
                $organization->name,
                $organization->slug,
                $organization->customDomain,
                $organization->isActive,
                $organization->aiSummaryEnabled,
                $organization->notificationEmail,
                $organization->webhookUrl,
                $organization->updatedAt,
                $organization->organizationId,
            ],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydrate(array $row): Organization
    {
        return new Organization(
            organizationId: (string) $row['organization_id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            isActive: (bool) $row['is_active'],
            customDomain: $row['custom_domain'] !== null ? (string) $row['custom_domain'] : null,
            aiSummaryEnabled: (bool) $row['ai_summary_enabled'],
            notificationEmail: $row['notification_email'] !== null ? (string) $row['notification_email'] : null,
            webhookUrl: $row['webhook_url'] !== null ? (string) $row['webhook_url'] : null,
            createdAt: $row['created_at'] !== null ? (string) $row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
        );
    }
}
