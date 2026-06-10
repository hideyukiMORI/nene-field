<?php

declare(strict_types=1);

namespace NeneField\Auth;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\Http\ClockInterface;
use NeneField\User\UserRepositoryInterface;

/**
 * Authenticates an operator (email + password, scoped to the resolved org) and
 * issues a bearer JWT carrying `sub` (user UUID), `role`, and `org` (org UUID),
 * so downstream middleware can authorize without a DB round-trip (24h TTL, R5.1).
 */
final readonly class LoginUseCase implements LoginUseCaseInterface
{
    private const TOKEN_TTL_SECONDS = 86_400;

    public function __construct(
        private UserRepositoryInterface $users,
        private TokenIssuerInterface $tokenIssuer,
        private ClockInterface $clock,
    ) {
    }

    public function execute(LoginInput $input): LoginOutput
    {
        $user = $this->users->findByEmailInOrg($input->organizationId, $input->email);

        // Same generic failure for unknown user / wrong password / inactive account
        // so account existence and status are not disclosed (no enumeration).
        if ($user === null || !password_verify($input->password, $user->passwordHash) || !$user->isActive) {
            throw new InvalidCredentialsException();
        }

        $now = $this->clock->now()->getTimestamp();

        $token = $this->tokenIssuer->issue([
            'sub' => $user->userId,
            'role' => $user->role->value,
            'org' => $user->organizationId,
            'iat' => $now,
            'exp' => $now + self::TOKEN_TTL_SECONDS,
        ]);

        return new LoginOutput($token, $user);
    }
}
