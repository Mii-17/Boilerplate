<?php

declare(strict_types=1);

use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Shared\Domain\ValueObjects\Email;

function createTestUser(
    ?UserId $id = null,
    string $name = 'John Doe',
    ?Email $email = null,
    ?PasswordHash $passwordHash = null,
    ?RolesCollection $roles = null
): User {
    return User::register(
        id: $id ?? UserId::generate(),
        name: $name,
        email: $email ?? Email::fromString('test@example.com'),
        passwordHash: $passwordHash ?? PasswordHash::fromPlainText(PlainPassword::fromString('Password123!')),
        roles: $roles ?? new RolesCollection([Role::USER])
    );
}

test('can register a new user with default role and record registration event', function (): void {
    $id = UserId::generate();
    $name = 'John Doe';
    $email = Email::fromString('test@example.com');

    $user = createTestUser(id: $id, name: $name, email: $email);

    expect($user->id()->equals($id))->toBeTrue()
        ->and($user->name())->toBe($name)
        ->and($user->email()->equals($email))->toBeTrue()
        ->and($user->hasRole(Role::USER))->toBeTrue()
        ->and($user->isVerified())->toBeFalse();

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserRegistered::class);
});

test('can verify an unverified user and record verification event', function (): void {
    $user = createTestUser();
    $user->pullDomainEvents();

    $user->verify();

    expect($user->isVerified())->toBeTrue();

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserVerified::class);
});

test('can change unverified email and record event', function (): void {
    $user = createTestUser(email: Email::fromString('old@example.com'));
    $user->pullDomainEvents();

    $newEmail = Email::fromString('new@example.com');
    $user->changeUnverifiedEmail($newEmail);

    expect($user->email()->equals($newEmail))->toBeTrue();

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UnverifiedUserChangedEmail::class);
});
