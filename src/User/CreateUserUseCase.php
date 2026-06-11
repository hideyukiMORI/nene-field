<?php

declare(strict_types=1);

namespace NeneField\User;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Auth\Role;
use NeneField\Support\Uuid;

final readonly class CreateUserUseCase implements CreateUserUseCaseInterface
{
    /** bcrypt cost (NF9: cost >= 12), matching ChangePasswordUseCase. */
    private const BCRYPT_COST = 12;

    /**
     * @param Closure(DatabaseQueryExecutorInterface): AuditRecorderInterface $auditFactory
     */
    public function __construct(
        private UserRepositoryInterface $users,
        private DatabaseTransactionManagerInterface $tx,
        private Closure $auditFactory,
        private ClockInterface $clock,
    ) {
    }

    public function execute(CreateUserInput $input): User
    {
        if ($input->role === Role::Superadmin) {
            throw new RoleNotAssignableException($input->role);
        }

        if ($this->users->findByEmailInOrg($input->organizationId, $input->email) !== null) {
            throw new UserEmailConflictException();
        }

        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $user = new User(
            userId: Uuid::v4(),
            organizationId: $input->organizationId,
            name: $input->name,
            email: $input->email,
            passwordHash: password_hash($input->password, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]),
            role: $input->role,
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($user, $input): void {
            $this->users->insert($exec, $user);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $input->organizationId,
                'user.created',
                'User',
                $user->userId,
                null,
                UserResponse::toArray($user),
            );
        });

        return $user;
    }
}
