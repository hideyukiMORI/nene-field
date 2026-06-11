<?php

declare(strict_types=1);

namespace NeneField\Tests\User;

use Closure;
use DateTimeImmutable;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use NeneField\AuditEvent\AuditRecorder;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Auth\Role;
use NeneField\Tests\Support\FixedClock;
use NeneField\User\CannotDeleteSelfException;
use NeneField\User\CreateUserInput;
use NeneField\User\CreateUserUseCase;
use NeneField\User\DeleteUserUseCase;
use NeneField\User\ListUsersUseCase;
use NeneField\User\PdoUserRepository;
use NeneField\User\RoleNotAssignableException;
use NeneField\User\UpdateUserInput;
use NeneField\User\UpdateUserUseCase;
use NeneField\User\UserEmailConflictException;
use NeneField\User\UserNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end user management against file SQLite, exercising the real transaction
 * manager + audit recorder: every mutation persists and writes its `user.*` audit
 * event in the same transaction (ADR 0014), scoped to the organization.
 */
final class UserManagementTest extends TestCase
{
    private const ORG = 'org-1';
    private const ADMIN = 'admin-1';

    private string $dbPath;
    private PdoConnectionFactory $factory;
    private PdoUserRepository $users;
    private FixedClock $clock;
    /** @var Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface */
    private Closure $auditFactory;
    private PdoDatabaseTransactionManager $tx;

    protected function setUp(): void
    {
        parent::setUp();

        $tmp = tempnam(sys_get_temp_dir(), 'nf_user_');
        self::assertIsString($tmp);
        $this->dbPath = $tmp;

        $this->factory = new PdoConnectionFactory(DatabaseConfig::sqlite($this->dbPath, 'test'));
        $setup = new PdoDatabaseQueryExecutor($this->factory);
        $setup->execute(UserSchema::CREATE_USERS_TABLE);
        $setup->execute(UserSchema::CREATE_AUDIT_TABLE);

        $this->users = new PdoUserRepository(new PdoDatabaseQueryExecutor($this->factory));
        $this->clock = new FixedClock(new DateTimeImmutable('2026-06-11T08:00:00Z'));
        $clock = $this->clock;
        $this->auditFactory = static fn (DatabaseQueryExecutorInterface $exec): AuditRecorderInterface
            => new AuditRecorder($exec, $clock);
        $this->tx = new PdoDatabaseTransactionManager($this->factory);
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        parent::tearDown();
    }

    public function test_full_lifecycle_persists_and_audits(): void
    {
        $created = $this->createUseCase()->execute(new CreateUserInput(
            organizationId: self::ORG,
            actorId: self::ADMIN,
            name: '田中太郎',
            email: 'tanaka@example.com',
            role: Role::Submitter,
            password: 'passw0rd',
        ));
        self::assertSame(Role::Submitter, $created->role);
        self::assertTrue($created->isActive);
        self::assertSame(1, $this->auditCount('user.created', $created->userId));

        // password is hashed, never stored as plaintext
        self::assertTrue(password_verify('passw0rd', $created->passwordHash));

        // list + count are tenant-scoped
        $output = (new ListUsersUseCase($this->users))->execute(self::ORG, 20, 0);
        self::assertSame(1, $output->total);
        self::assertCount(1, $output->items);

        // update role + active flag, keeping email
        $updated = (new UpdateUserUseCase($this->users, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateUserInput(self::ORG, self::ADMIN, $created->userId, name: null, role: Role::Approver, isActive: false),
        );
        self::assertSame(Role::Approver, $updated->role);
        self::assertFalse($updated->isActive);
        self::assertSame('tanaka@example.com', $updated->email);
        self::assertSame(1, $this->auditCount('user.updated', $created->userId));

        // delete + audit
        (new DeleteUserUseCase($this->users, $this->tx, $this->auditFactory))->execute(self::ORG, self::ADMIN, $created->userId);
        self::assertNull($this->users->findById(self::ORG, $created->userId));
        self::assertSame(1, $this->auditCount('user.deleted', $created->userId));
    }

    public function test_duplicate_email_in_org_is_rejected(): void
    {
        $this->createUseCase()->execute(new CreateUserInput(self::ORG, self::ADMIN, 'A', 'dup@example.com', Role::Submitter, 'passw0rd'));

        $this->expectException(UserEmailConflictException::class);
        $this->createUseCase()->execute(new CreateUserInput(self::ORG, self::ADMIN, 'B', 'dup@example.com', Role::Approver, 'passw0rd'));
    }

    public function test_superadmin_role_cannot_be_assigned_on_create(): void
    {
        $this->expectException(RoleNotAssignableException::class);
        $this->createUseCase()->execute(new CreateUserInput(self::ORG, self::ADMIN, 'X', 'x@example.com', Role::Superadmin, 'passw0rd'));
    }

    public function test_superadmin_role_cannot_be_assigned_on_update(): void
    {
        $created = $this->createUseCase()->execute(new CreateUserInput(self::ORG, self::ADMIN, 'X', 'x@example.com', Role::Submitter, 'passw0rd'));

        $this->expectException(RoleNotAssignableException::class);
        (new UpdateUserUseCase($this->users, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateUserInput(self::ORG, self::ADMIN, $created->userId, name: null, role: Role::Superadmin, isActive: null),
        );
    }

    public function test_admin_cannot_delete_self(): void
    {
        $created = $this->createUseCase()->execute(new CreateUserInput(self::ORG, self::ADMIN, 'X', 'x@example.com', Role::Admin, 'passw0rd'));

        $this->expectException(CannotDeleteSelfException::class);
        (new DeleteUserUseCase($this->users, $this->tx, $this->auditFactory))->execute(self::ORG, $created->userId, $created->userId);
    }

    public function test_user_in_another_org_is_not_found_on_update(): void
    {
        $created = $this->createUseCase()->execute(new CreateUserInput(self::ORG, self::ADMIN, 'X', 'x@example.com', Role::Submitter, 'passw0rd'));

        $this->expectException(UserNotFoundException::class);
        (new UpdateUserUseCase($this->users, $this->tx, $this->auditFactory, $this->clock))->execute(
            new UpdateUserInput('org-2', self::ADMIN, $created->userId, name: 'hacked', role: null, isActive: null),
        );
    }

    public function test_list_is_scoped_to_organization(): void
    {
        $this->createUseCase()->execute(new CreateUserInput(self::ORG, self::ADMIN, 'A', 'a@example.com', Role::Submitter, 'passw0rd'));
        $this->createUseCase()->execute(new CreateUserInput('org-2', self::ADMIN, 'B', 'b@example.com', Role::Submitter, 'passw0rd'));

        $output = (new ListUsersUseCase($this->users))->execute(self::ORG, 20, 0);
        self::assertSame(1, $output->total);
        self::assertSame('a@example.com', $output->items[0]->email);
    }

    private function createUseCase(): CreateUserUseCase
    {
        return new CreateUserUseCase($this->users, $this->tx, $this->auditFactory, $this->clock);
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
