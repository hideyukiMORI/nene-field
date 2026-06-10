<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Http\ClockInterface;
use NeneField\User\UserRepositoryInterface;

final readonly class ChangePasswordUseCase implements ChangePasswordUseCaseInterface
{
    /** bcrypt cost (NF9: cost >= 12). */
    private const BCRYPT_COST = 12;

    public function __construct(
        private UserRepositoryInterface $users,
        private ClockInterface $clock,
    ) {
    }

    public function execute(ChangePasswordInput $input): void
    {
        $user = $this->users->findById($input->organizationId, $input->userId);

        if ($user === null || !password_verify($input->currentPassword, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        $hash = password_hash($input->newPassword, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->users->updatePasswordHash($input->organizationId, $input->userId, $hash, $now);
    }
}
