<?php

declare(strict_types=1);

namespace NeneField\Tests\Auth;

use DateTimeImmutable;
use DateTimeZone;
use Nene2\Auth\LocalBearerTokenVerifier;
use NeneField\Auth\InvalidCredentialsException;
use NeneField\Auth\LoginInput;
use NeneField\Auth\LoginUseCase;
use NeneField\Auth\Role;
use NeneField\Tests\Support\FixedClock;
use NeneField\Tests\Support\InMemoryUserRepository;
use NeneField\User\User;
use PHPUnit\Framework\TestCase;

final class LoginUseCaseTest extends TestCase
{
    private const ORG = 'org-1';

    private LocalBearerTokenVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new LocalBearerTokenVerifier('test-secret');
    }

    public function test_valid_login_issues_token_with_claims_and_returns_user(): void
    {
        $useCase = $this->useCase([$this->user('secret', true, self::ORG)]);

        $output = $useCase->execute(new LoginInput(self::ORG, 'tanaka@example.com', 'secret'));

        self::assertNotSame('', $output->token);
        self::assertSame('user-1', $output->user->userId);

        $claims = $this->verifier->verify($output->token);
        self::assertSame('user-1', $claims['sub']);
        self::assertSame('admin', $claims['role']);
        self::assertSame(self::ORG, $claims['org']);
        self::assertIsInt($claims['exp']);
        // The token must not be already expired at verification time; this guards
        // against regressing to a fixed past issuance clock (#19).
        self::assertGreaterThan(time(), $claims['exp']);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $useCase = $this->useCase([$this->user('secret', true, self::ORG)]);

        $this->expectException(InvalidCredentialsException::class);
        $useCase->execute(new LoginInput(self::ORG, 'tanaka@example.com', 'wrong'));
    }

    public function test_inactive_user_is_rejected(): void
    {
        $useCase = $this->useCase([$this->user('secret', false, self::ORG)]);

        $this->expectException(InvalidCredentialsException::class);
        $useCase->execute(new LoginInput(self::ORG, 'tanaka@example.com', 'secret'));
    }

    public function test_login_is_scoped_to_the_resolved_org(): void
    {
        // User belongs to ORG; logging in under a different org must fail.
        $useCase = $this->useCase([$this->user('secret', true, self::ORG)]);

        $this->expectException(InvalidCredentialsException::class);
        $useCase->execute(new LoginInput('other-org', 'tanaka@example.com', 'secret'));
    }

    /**
     * @param list<User> $users
     */
    private function useCase(array $users): LoginUseCase
    {
        // The issued token's `exp` is verified by LocalBearerTokenVerifier against
        // the real system clock (time()), which cannot be injected. Anchoring
        // issuance to a hard-coded past instant made the 24h TTL window expire as
        // wall-clock time advanced, so the exp check failed non-deterministically
        // (#19). Seed the FixedClock from real "now" (UTC) so the validity window
        // always straddles the verification instant, independent of the calendar.
        return new LoginUseCase(
            new InMemoryUserRepository($users),
            $this->verifier,
            new FixedClock(new DateTimeImmutable('now', new DateTimeZone('UTC'))),
        );
    }

    private function user(string $password, bool $isActive, string $organizationId): User
    {
        return new User(
            userId: 'user-1',
            organizationId: $organizationId,
            name: '田中',
            email: 'tanaka@example.com',
            passwordHash: password_hash($password, PASSWORD_BCRYPT),
            role: Role::Admin,
            isActive: $isActive,
        );
    }
}
