<?php

declare(strict_types=1);

namespace NeneField\Tests\AuditEvent;

use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Proves the ADR 0014 guarantee: a mutation and its audit row are written in the
 * SAME transaction — they commit together, and a failure rolls back BOTH (no
 * orphan audit row). Uses a file-backed SQLite so the transaction manager's
 * connection and the read connection share one database.
 */
final class AuditRecorderTransactionTest extends TestCase
{
    private string $dbPath;
    private PdoConnectionFactory $factory;
    private PdoDatabaseTransactionManager $tx;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = tempnam(sys_get_temp_dir(), 'nf_audit_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $this->tx = new PdoDatabaseTransactionManager($this->factory);

        $setup = new PdoDatabaseQueryExecutor($this->factory);
        $setup->execute('CREATE TABLE things (id CHAR(36) NOT NULL PRIMARY KEY, name VARCHAR(255) NOT NULL)');
        $setup->execute(
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

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        parent::tearDown();
    }

    public function test_mutation_and_audit_commit_together(): void
    {
        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec): void {
            $exec->execute('INSERT INTO things (id, name) VALUES (?, ?)', ['thing-1', 'A']);
            $this->recorder($exec)->record('user-1', 'org-1', 'thing.created', 'Thing', 'thing-1', null, ['name' => 'A']);
        });

        self::assertSame(1, $this->countRows('things'));
        self::assertSame(1, $this->countRows('audit_events'));
    }

    public function test_failure_rolls_back_both_mutation_and_audit(): void
    {
        try {
            $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec): void {
                $exec->execute('INSERT INTO things (id, name) VALUES (?, ?)', ['thing-2', 'B']);
                $this->recorder($exec)->record('user-1', 'org-1', 'thing.created', 'Thing', 'thing-2', null, ['name' => 'B']);

                throw new RuntimeException('boom after writes');
            });
        } catch (RuntimeException $e) {
            self::assertSame('boom after writes', $e->getMessage());
        }

        // Neither the mutation nor the audit row survived the rollback.
        self::assertSame(0, $this->countRows('things'));
        self::assertSame(0, $this->countRows('audit_events'));
    }

    private function recorder(DatabaseQueryExecutorInterface $exec): AuditRecorder
    {
        return new AuditRecorder($exec, new FixedClock(new DateTimeImmutable('2026-06-11T00:00:00Z')));
    }

    private function countRows(string $table): int
    {
        $read = new PdoDatabaseQueryExecutor($this->factory);
        $row = $read->fetchOne('SELECT COUNT(*) AS c FROM ' . $table);

        return $row !== null ? (int) $row['c'] : -1;
    }
}
