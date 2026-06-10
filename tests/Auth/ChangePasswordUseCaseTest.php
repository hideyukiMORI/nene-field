<?php

declare(strict_types=1);

namespace NeneField\Tests\Auth;

use DateTimeImmutable;
use NeneField\Auth\ChangePasswordInput;
use NeneField\Auth\ChangePasswordUseCase;
use NeneField\Auth\InvalidCredentialsException;
use NeneField\Auth\Role;
use NeneField\Tests\Support\FixedClock;
use NeneField\Tests\Support\InMemoryUserRepository;
use NeneField\User\User;
use PHPUnit\Framework\TestCase;

final class ChangePasswordUseCaseTest extends TestCase
{
    private const ORG = 'org-1';

    public function test_changes_password_when_current_is_correct(): void
    {
        $users = new InMemoryUserRepository([$this->user('old-password')]);
        $useCase = new ChangePasswordUseCase($users, new FixedClock(new DateTimeImmutable('2026-06-11T00:00:00Z')));

        $useCase->execute(new ChangePasswordInput(self::ORG, 'user-1', 'old-password', 'new-password'));

        $updated = $users->findById(self::ORG, 'user-1');
        self::assertNotNull($updated);
        self::assertTrue(password_verify('new-password', $updated->passwordHash));
        self::assertFalse(password_verify('old-password', $updated->passwordHash));
    }

    public function test_rejects_wrong_current_password(): void
    {
        $users = new InMemoryUserRepository([$this->user('old-password')]);
        $useCase = new ChangePasswordUseCase($users, new FixedClock(new DateTimeImmutable('2026-06-11T00:00:00Z')));

        $this->expectException(InvalidCredentialsException::class);
        $useCase->execute(new ChangePasswordInput(self::ORG, 'user-1', 'wrong', 'new-password'));
    }

    private function user(string $password): User
    {
        return new User(
            userId: 'user-1',
            organizationId: self::ORG,
            name: '田中',
            email: 'tanaka@example.com',
            passwordHash: password_hash($password, PASSWORD_BCRYPT),
            role: Role::Submitter,
            isActive: true,
        );
    }
}
