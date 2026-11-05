<?php

declare(strict_types=1);

use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Models\UserRoleModel;
use Src\Iam\User\Infrastructure\Persistence\EloquentUserRepository;
use Src\Shared\Domain\ValueObjects\Email;

function eloquentUserRepository(): EloquentUserRepository
{
    return app(EloquentUserRepository::class);
}

function dummyPasswordHash(): PasswordHash
{
    return PasswordHash::fromHash(config()->string('iam.dummy_password_hash'));
}

test('saves a domain user aggregate into database and finds it by id', function (): void {
    $userId = UserId::generate();
    $user = User::register(
        'John Doe',
        Email::fromString('john@example.com'),
        dummyPasswordHash(),
        new RolesCollection([Role::USER]),
        $userId
    );

    $repository = eloquentUserRepository();
    $repository->save($user);

    $savedUser = $repository->findById($userId);

    assert($savedUser instanceof User);

    expect($savedUser->id()->equals($userId))->toBeTrue()
        ->and($savedUser->name())->toBe('John Doe')
        ->and($savedUser->email()->equals(Email::fromString('john@example.com')))->toBeTrue()
        ->and($savedUser->passwordHash()->equals(dummyPasswordHash()))->toBeTrue()
        ->and($savedUser->roles()->all())->toBe([Role::USER])
        ->and($savedUser->isVerified())->toBeFalse()
        ->and($savedUser->verificationToken()?->value())->toBe($user->verificationToken()?->value());
});

test('finds a domain user by email', function (): void {
    $userId = UserId::generate();
    $email = Email::fromString('john@example.com');
    $user = User::register(
        'John Doe',
        $email,
        dummyPasswordHash(),
        new RolesCollection([Role::USER]),
        $userId
    );

    $repository = eloquentUserRepository();
    $repository->save($user);

    $savedUser = $repository->findByEmail($email);

    assert($savedUser instanceof User);

    expect($savedUser->id()->equals($userId))->toBeTrue()
        ->and($savedUser->name())->toBe('John Doe')
        ->and($savedUser->email()->equals($email))->toBeTrue()
        ->and($savedUser->roles()->all())->toBe([Role::USER]);
});

test('finds a domain user by verification token', function (): void {
    $userId = UserId::generate();
    $user = User::register(
        'John Doe',
        Email::fromString('john@example.com'),
        dummyPasswordHash(),
        new RolesCollection([Role::USER]),
        $userId
    );

    $token = $user->verificationToken();

    assert($token instanceof VerificationToken);

    $repository = eloquentUserRepository();
    $repository->save($user);

    $savedUser = $repository->findByVerificationToken($token);

    assert($savedUser instanceof User);

    expect($savedUser->id()->equals($userId))->toBeTrue();
});

test('checks email existence using the normalized email', function (): void {
    $repository = eloquentUserRepository();

    expect($repository->existsByEmail(Email::fromString('john@example.com')))->toBeFalse();

    $user = User::register(
        'John Doe',
        Email::fromString('john@example.com'),
        dummyPasswordHash(),
        new RolesCollection([Role::USER]),
        UserId::generate()
    );

    $repository->save($user);

    expect($repository->existsByEmail(Email::fromString('  JOHN@EXAMPLE.COM ')))->toBeTrue();
});

test('returns null when a user is not found', function (): void {
    $repository = eloquentUserRepository();

    expect($repository->findById(UserId::generate()))->toBeNull()
        ->and($repository->findByEmail(Email::fromString('missing@example.com')))->toBeNull()
        ->and($repository->findByVerificationToken(VerificationToken::generate()))->toBeNull();
});

test('updates an existing user and syncs its roles', function (): void {
    $userId = UserId::generate();
    $email = Email::fromString('john@example.com');

    $user = User::register(
        'John Doe',
        $email,
        dummyPasswordHash(),
        new RolesCollection([Role::USER]),
        $userId
    );

    $repository = eloquentUserRepository();
    $repository->save($user);

    $updatedUser = User::reconstitute(
        $userId,
        'John Updated',
        $email,
        dummyPasswordHash(),
        new RolesCollection([Role::USER, Role::ADMIN]),
        new DateTimeImmutable,
        null
    );

    $repository->save($updatedUser);

    $savedUser = $repository->findById($userId);

    assert($savedUser instanceof User);

    expect($savedUser->name())->toBe('John Updated')
        ->and($savedUser->roles()->all())->toEqualCanonicalizing([Role::USER, Role::ADMIN])
        ->and($savedUser->isVerified())->toBeTrue()
        ->and(UserModel::query()->whereKey($userId->value())->count())->toBe(1)
        ->and(UserRoleModel::query()->where('user_id', $userId->value())->count())->toBe(2);
});

test('resolves UserRepositoryInterface to EloquentUserRepository from container', function (): void {
    expect(app(UserRepositoryInterface::class))->toBeInstanceOf(EloquentUserRepository::class);
});
