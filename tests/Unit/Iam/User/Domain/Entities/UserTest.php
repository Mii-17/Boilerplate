<?php

declare(strict_types=1);

use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidVerificationTokenException;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;

/**
 * @return array{0: User, 1: VerificationToken}
 */
function createTestUser(
    ?UserId $id = null,
    string $name = 'John Doe',
    ?Email $email = null,
    ?PasswordHash $passwordHash = null,
    ?RolesCollection $roles = null,
    ?VerificationToken $verificationToken = null
): array {
    $token = $verificationToken ?? VerificationToken::generate();

    $user = User::register(
        name: $name,
        email: $email ?? Email::fromString('test@example.com'),
        passwordHash: $passwordHash ?? PasswordHash::fromHash('hashed_password'),
        roles: $roles ?? new RolesCollection([Role::USER]),
        id: $id ?? UserId::generate(),
        verificationToken: $token
    );

    return [$user, $token];
}

test('can register a new user with default role and record registration event', function (): void {
    $id = UserId::generate();
    $name = 'John Doe';
    $email = Email::fromString('test@example.com');

    [$user, $token] = createTestUser(id: $id, name: $name, email: $email);

    expect($user->id()->equals($id))->toBeTrue()
        ->and($user->name())->toBe($name)
        ->and($user->email()->equals($email))->toBeTrue()
        ->and($user->hasRole(Role::USER))->toBeTrue()
        ->and($user->isVerified())->toBeFalse()
        ->and($user->verificationToken())->not->toBeNull();

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserRegistered::class);
});

test('can verify an unverified user and record verification event', function (): void {
    [$user, $token] = createTestUser();
    $user->pullDomainEvents();

    $user->verify($token->value());

    expect($user->isVerified())->toBeTrue()
        ->and($user->verificationToken())->toBeNull();

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserVerified::class);
});

test('can change unverified email and record event', function (): void {
    [$user, $token] = createTestUser(email: Email::fromString('old@example.com'));
    $user->pullDomainEvents();

    $newEmail = Email::fromString('new@example.com');
    $user->changeUnverifiedEmail($newEmail);

    expect($user->email()->equals($newEmail))->toBeTrue();

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UnverifiedUserChangedEmail::class);
});

test('cannot verify a user with an expired token', function (): void {
    $expiredToken = new VerificationToken(
        'expired-test-token',
        new DateTimeImmutable('-1 hour'),
    );

    [$user] = createTestUser(verificationToken: $expiredToken);

    $user->pullDomainEvents();

    expect(fn () => $user->verify($expiredToken->value()))
        ->toThrow(InvalidVerificationTokenException::class);

    expect($user->isVerified())->toBeFalse()
        ->and($user->verificationToken())->not->toBeNull()
        ->and($user->pullDomainEvents())->toBeEmpty();
});

test('changeUnverifiedEmail generates a new verification token with a renewed expiration date', function (): void {
    [$user, $token] = createTestUser(email: Email::fromString('old@example.com'));
    $user->pullDomainEvents();

    $newEmail = Email::fromString('new@example.com');
    $user->changeUnverifiedEmail($newEmail);

    $newVerificationToken = $user->verificationToken();
    assert($newVerificationToken instanceof VerificationToken);

    expect($token->value())
        ->not->toBe($newVerificationToken->value())
        ->and($newVerificationToken->expiresAt())
        ->toBeGreaterThan($token->expiresAt());
});
