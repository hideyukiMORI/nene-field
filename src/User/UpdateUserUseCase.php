<?php

declare(strict_types=1);

namespace NeneField\User;

use Closure;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use NeneField\AuditEvent\AuditRecorderInterface;
use NeneField\Auth\Role;

final readonly class UpdateUserUseCase implements UpdateUserUseCaseInterface
{
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

    public function execute(UpdateUserInput $input): User
    {
        $existing = $this->users->findById($input->organizationId, $input->userId);

        if ($existing === null) {
            throw new UserNotFoundException();
        }

        if ($input->role === Role::Superadmin) {
            throw new RoleNotAssignableException($input->role);
        }

        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $updated = new User(
            userId: $existing->userId,
            organizationId: $existing->organizationId,
            name: $input->name ?? $existing->name,
            email: $existing->email,
            passwordHash: $existing->passwordHash,
            role: $input->role ?? $existing->role,
            isActive: $input->isActive ?? $existing->isActive,
            createdAt: $existing->createdAt,
            updatedAt: $now,
        );

        $this->tx->transactional(function (DatabaseQueryExecutorInterface $exec) use ($existing, $updated, $input): void {
            $this->users->update($exec, $updated);
            ($this->auditFactory)($exec)->record(
                $input->actorId,
                $input->organizationId,
                'user.updated',
                'User',
                $updated->userId,
                UserResponse::toArray($existing),
                UserResponse::toArray($updated),
            );
        });

        return $updated;
    }
}
