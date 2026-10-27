<?php

declare(strict_types=1);

use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\DTOs\VerifyEmailCommand;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Application\UseCases\VerifyEmailUseCase;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidVerificationTokenException;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;
use Tests\Doubles\Cache\InMemoryIdempotencyChecker;
use Tests\Doubles\Crypt\FakeHasher;
use Tests\Doubles\Events\FakeEventDispatcher;
use Tests\Doubles\Persistence\DummyTransactionManager;
use Tests\Doubles\Repositories\InMemoryUserRepository;

/**
 * @return array{0: RegisterUserUseCase, 1: InMemoryUserRepository, 2: FakeEventDispatcher, 3: VerifyEmailUseCase}
 */
function createUsesCaseContext(): array
{
    $repository = new InMemoryUserRepository;
    $eventDispatcher = new FakeEventDispatcher;
    $hasher = new FakeHasher;
    $transactionManager = new DummyTransactionManager;
    /** @var InMemoryIdempotencyChecker<User> $idempotencyChecker */
    $idempotencyChecker = new InMemoryIdempotencyChecker;

    $registerUseCase = new RegisterUserUseCase(
        userRepository: $repository,
        eventDispatcher: $eventDispatcher,
        hasher: $hasher,
        transactionManager: $transactionManager,
        idempotencyChecker: $idempotencyChecker,
    );

    $verifyUseCase = new VerifyEmailUseCase(
        userRepository: $repository,
        eventDispatcher: $eventDispatcher,
        transactionManager: $transactionManager,
        idempotencyChecker: $idempotencyChecker, );

    return [$registerUseCase, $repository, $eventDispatcher, $verifyUseCase];
}

test('user can verify email with a valid token', function (): void {
    [$registerUseCase, $repository, $eventDispatcher, $verifyUseCase] = createUsesCaseContext();

    $userName = 'User Test';
    $userEmail = 'user@example.com';
    $userPassword = 'Secret123!';
    $userRoles = [Role::USER->value];

    $registerCommand = new RegisterUserCommand($userName, $userEmail, $userPassword, $userRoles);

    $registerUseCase->execute($registerCommand);

    $user = $repository->findByEmail(Email::fromString($userEmail));

    expect($user)->toBeInstanceOf(User::class);
    assert($user instanceof User);

    $tokenValue = $user->verificationToken();
    expect($tokenValue)->toBeInstanceOf(VerificationToken::class);
    assert($tokenValue instanceof VerificationToken);

    $verifyCommand = new VerifyEmailCommand($user->id()->value(), $tokenValue->value());

    $verifyUseCase->execute($verifyCommand);

    $updatedUser = $repository->findByEmail(Email::fromString($userEmail));

    expect($updatedUser)->toBeInstanceOf(User::class);
    assert($updatedUser instanceof User);

    expect($updatedUser->isVerified())->toBeTrue()
        ->and($eventDispatcher->hasDispatched(UserVerified::class))->toBeTrue();
});

test('user can not verify email with an invalid token', function (): void {
    [$registerUseCase, $repository,, $verifyUseCase] = createUsesCaseContext();

    $userName = 'User Test';
    $userEmail = 'user@example.com';
    $userPassword = 'Secret123!';
    $userRoles = [Role::USER->value];

    $registerCommand = new RegisterUserCommand($userName, $userEmail, $userPassword, $userRoles);

    $registerUseCase->execute($registerCommand);

    $user = $repository->findByEmail(Email::fromString($userEmail));

    expect($user)->toBeInstanceOf(User::class);
    assert($user instanceof User);

    $verifyCommand = new VerifyEmailCommand($user->id()->value(), 'invalid-token-string');

    expect(fn () => $verifyUseCase->execute($verifyCommand))
        ->toThrow(InvalidVerificationTokenException::class);
});

test('user can not verify email if user does not exist', function (): void {
    [,,, $verifyUseCase] = createUsesCaseContext();

    $nonExistentUserId = '00000000-0000-0000-0000-000000000000';
    $verifyCommand = new VerifyEmailCommand($nonExistentUserId, 'some-token');

    expect(fn () => $verifyUseCase->execute($verifyCommand))
        ->toThrow(UserNotFoundException::class);
});

test('prevents duplicate execution and returns cached result when idempotency key is reused', function (): void {
    [$registerUseCase, $repository, $eventDispatcher, $verifyUseCase] = createUsesCaseContext();
    $userName = 'User Test';
    $userEmail = 'user@example.com';
    $userPassword = 'Secret123!';
    $userRoles = [Role::USER->value];

    $registerCommand = new RegisterUserCommand($userName, $userEmail, $userPassword, $userRoles);

    $registerUseCase->execute($registerCommand);

    $user = $repository->findByEmail(Email::fromString($userEmail));

    expect($user)->toBeInstanceOf(User::class);
    assert($user instanceof User);

    $tokenValue = $user->verificationToken();
    expect($tokenValue)->toBeInstanceOf(VerificationToken::class);
    assert($tokenValue instanceof VerificationToken);

    $verifyCommand = new VerifyEmailCommand(
        $user->id()->value(),
        $tokenValue->value(),
        'idem-uuid-12345'
    );

    $verifyUseCase->execute($verifyCommand);
    $verifyUseCase->execute($verifyCommand);

    expect($eventDispatcher->dispatchedEvents())->toHaveCount(2)
        ->and($eventDispatcher->hasDispatched(UserRegistered::class))->toBeTrue()
        ->and($eventDispatcher->hasDispatched(UserVerified::class))->toBeTrue();
});
