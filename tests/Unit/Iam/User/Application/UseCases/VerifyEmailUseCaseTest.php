<?php

declare(strict_types=1);

use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\DTOs\VerifyEmailCommand;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Application\UseCases\VerifyEmailUseCase;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Domain\Exceptions\UserAlreadyVerifiedException;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidVerificationTokenException;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;
use Tests\Doubles\Crypt\FakeHasher;
use Tests\Doubles\Events\FakeEventDispatcher;
use Tests\Doubles\Persistence\DummyTransactionManager;
use Tests\Doubles\Repositories\InMemoryUserRepository;

/**
 * @return array{
 *     0: RegisterUserUseCase,
 *     1: InMemoryUserRepository,
 *     2: FakeEventDispatcher,
 *     3: VerifyEmailUseCase
 * }
 */
function createUsesCaseContext(): array
{
    $repository = new InMemoryUserRepository;
    $eventDispatcher = new FakeEventDispatcher;
    $hasher = new FakeHasher;
    $transactionManager = new DummyTransactionManager;

    $registerUseCase = new RegisterUserUseCase(
        userRepository: $repository,
        hasher: $hasher,
        transactionManager: $transactionManager,
        eventDispatcher: $eventDispatcher,
    );

    $verifyUseCase = new VerifyEmailUseCase(
        userRepository: $repository,
        transactionManager: $transactionManager,
        eventDispatcher: $eventDispatcher,
    );

    return [$registerUseCase, $repository, $eventDispatcher, $verifyUseCase];
}

/**
 * Registers a user and returns the persisted aggregate.
 */
function registerPendingUser(
    RegisterUserUseCase $registerUseCase,
    InMemoryUserRepository $repository,
    string $email = 'user@example.com',
): User {
    $registerUseCase->execute(
        new RegisterUserCommand(
            name: 'User Test',
            email: $email,
            plainPassword: 'Secret123!',
        ),
    );

    $user = $repository->findByEmail(Email::fromString($email));

    expect($user)->toBeInstanceOf(User::class);

    assert($user instanceof User);

    return $user;
}

test('user can verify email with a valid token', function (): void {
    [$registerUseCase, $repository, $eventDispatcher, $verifyUseCase] = createUsesCaseContext();

    $user = registerPendingUser($registerUseCase, $repository);
    $token = $user->verificationToken();

    assert($token instanceof VerificationToken);

    $response = $verifyUseCase->execute(
        new VerifyEmailCommand(
            userId: $user->id()->value(),
            token: $token->value(),
        ),
    );

    $updatedUser = $repository->findById($user->id());

    assert($updatedUser instanceof User);

    expect($updatedUser->isVerified())->toBeTrue()
        ->and($updatedUser->verificationToken())->toBeNull()
        ->and($response->isVerified)->toBeTrue()
        ->and($eventDispatcher->hasDispatched(UserVerified::class))->toBeTrue();
});

test('user cannot verify email with an invalid token', function (): void {
    [$registerUseCase, $repository, $eventDispatcher, $verifyUseCase] = createUsesCaseContext();

    $user = registerPendingUser($registerUseCase, $repository);

    expect(fn () => $verifyUseCase->execute(
        new VerifyEmailCommand(
            userId: $user->id()->value(),
            token: 'invalid-token-string',
        ),
    ))->toThrow(InvalidVerificationTokenException::class);

    $unchangedUser = $repository->findById($user->id());

    assert($unchangedUser instanceof User);

    expect($unchangedUser->isVerified())->toBeFalse()
        ->and($eventDispatcher->hasDispatched(UserVerified::class))->toBeFalse();
});

test('user cannot verify email if user does not exist', function (): void {
    [, , , $verifyUseCase] = createUsesCaseContext();

    expect(fn () => $verifyUseCase->execute(
        new VerifyEmailCommand(
            userId: '00000000-0000-0000-0000-000000000000',
            token: 'some-token',
        ),
    ))->toThrow(UserNotFoundException::class);
});

test('user cannot verify email more than once', function (): void {
    [$registerUseCase, $repository, $eventDispatcher, $verifyUseCase] = createUsesCaseContext();

    $user = registerPendingUser($registerUseCase, $repository);
    $token = $user->verificationToken();

    assert($token instanceof VerificationToken);

    $command = new VerifyEmailCommand(
        userId: $user->id()->value(),
        token: $token->value(),
    );

    $verifyUseCase->execute($command);

    expect(fn () => $verifyUseCase->execute($command))
        ->toThrow(UserAlreadyVerifiedException::class);

    expect($eventDispatcher->hasDispatched(UserVerified::class))->toBeTrue();
});

test('verification response contains the verified user state', function (): void {
    [$registerUseCase, $repository, , $verifyUseCase] = createUsesCaseContext();

    $user = registerPendingUser($registerUseCase, $repository);
    $token = $user->verificationToken();

    assert($token instanceof VerificationToken);

    $response = $verifyUseCase->execute(
        new VerifyEmailCommand(
            userId: $user->id()->value(),
            token: $token->value(),
        ),
    );

    expect($response->id)->toBe($user->id()->value())
        ->and($response->email)->toBe('user@example.com')
        ->and($response->isVerified)->toBeTrue();
});
