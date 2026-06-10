<?php

declare(strict_types=1);

namespace NeneField\Tests\AuditEvent;

use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

final class AuditRecorderTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();
        $factory = new PdoConnectionFactory(DatabaseConfig::sqlite(':memory:', 'test'));
        $this->executor = new PdoDatabaseQueryExecutor($factory);
        $this->executor->execute(
            'CREATE TABLE audit_events (
                event_id CHAR(36) NOT NULL PRIMARY KEY,
                organization_id CHAR(36) NOT NULL,
                actor_id CHAR(36) NULL,
                event_name VARCHAR(64) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id CHAR(36) NOT NULL,
                before_json TEXT NULL,
                after_json TEXT NULL,
                request_id VARCHAR(64) NULL,
                occurred_at DATETIME NOT NULL
            )',
        );
    }

    public function test_records_event_with_sanitized_after_and_request_id(): void
    {
        $recorder = new AuditRecorder(
            $this->executor,
            new FixedClock(new DateTimeImmutable('2026-06-11T03:04:05Z')),
            'req-abc',
        );

        $recorder->record('user-1', 'org-1', 'report.created', 'Report', 'rep-1', null, ['title' => '現場A']);

        $row = $this->executor->fetchOne('SELECT * FROM audit_events');
        self::assertNotNull($row);
        self::assertSame('org-1', $row['organization_id']);
        self::assertSame('user-1', $row['actor_id']);
        self::assertSame('report.created', $row['event_name']);
        self::assertSame('Report', $row['entity_type']);
        self::assertSame('rep-1', $row['entity_id']);
        self::assertNull($row['before_json']);
        self::assertSame('req-abc', $row['request_id']);
        self::assertSame('2026-06-11 03:04:05', $row['occurred_at']);

        $after = json_decode((string) $row['after_json'], true);
        self::assertSame(['title' => '現場A'], $after);
    }

    public function test_records_system_action_with_null_actor_and_before(): void
    {
        $recorder = new AuditRecorder($this->executor, new FixedClock(new DateTimeImmutable('2026-06-11T00:00:00Z')));

        $recorder->record(null, 'org-1', 'report.deleted', 'Report', 'rep-2', ['title' => 'old'], null);

        $row = $this->executor->fetchOne('SELECT * FROM audit_events');
        self::assertNotNull($row);
        self::assertNull($row['actor_id']);
        self::assertNull($row['after_json']);
        self::assertNull($row['request_id']);
        self::assertSame(['title' => 'old'], json_decode((string) $row['before_json'], true));
    }
}
