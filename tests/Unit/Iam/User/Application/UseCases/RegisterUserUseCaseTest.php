<?php

declare(strict_types=1);

use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\DTOs\RegisterUserResponse;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Exceptions\UserAlreadyExistsException;
use Src\Shared\Domain\ValueObjects\Email;
use Tests\Doubles\Crypt\FakeHasher;
use Tests\Doubles\Events\FakeEventDispatcher;
use Tests\Doubles\Persistence\SpyTransactionManager;
use Tests\Doubles\Repositories\InMemoryUserRepository;
use Tests\Doubles\Repositories\ThrowingUserRepository;

/**
 * @return array{0: RegisterUserUseCase, 1: InMemoryUserRepository, 2: FakeEventDispatcher, 3: SpyTransactionManager}
 */
function createRegisterUserUseCaseContext(): array
{
    $repository = new InMemoryUserRepository;
    $eventDispatcher = new FakeEventDispatcher;
    $hasher = new FakeHasher;
    $transactionManager = new SpyTransactionManager;

    $useCase = new RegisterUserUseCase(
        userRepository: $repository,
        hasher: $hasher,
        transactionManager: $transactionManager,
        eventDispatcher: $eventDispatcher,
    );

    return [$useCase, $repository, $eventDispatcher, $transactionManager];
}

test('registers a user successfully and dispatches a domain event', function (): void {
    [$useCase, $repository, $eventDispatcher, $transactionManager] = createRegisterUserUseCaseContext();

    $name = 'John Doe';
    $email = 'user@example.com';
    $userPassword = 'Secret123!';

    $command = new RegisterUserCommand(
        name: $name,
        email: $email,
        plainPassword: $userPassword,
    );

    $useCase->execute($command);

    $savedUser = $repository->findByEmail(Email::fromString($email));

    expect($savedUser)->toBeInstanceOf(User::class);
    assert($savedUser instanceof User);

    expect($savedUser->name())->toBe($name)
        ->and($transactionManager->wrappedOperationCount)->toBe(1)
        ->and($eventDispatcher->dispatchedEvents())->toHaveCount(1)
        ->and($eventDispatcher->hasDispatched(UserRegistered::class))->toBeTrue();
});

test('throws exception when email is already registered', function (): void {
    [$useCase] = createRegisterUserUseCaseContext();

    $email = 'user@example.com';
    $userPassword = 'Secret123!';

    $command1 = new RegisterUserCommand(
        name: 'User One',
        email: $email,
        plainPassword: $userPassword,
    );

    $command2 = new RegisterUserCommand(
        name: 'User Two',
        email: $email,
        plainPassword: $userPassword,
    );

    $useCase->execute($command1);

    expect(fn () => $useCase->execute($command2))
        ->toThrow(UserAlreadyExistsException::class);
});

test('propagates save failure out of the transaction', function (): void {
    $repository = new ThrowingUserRepository;
    $transactionManager = new SpyTransactionManager;
    $eventDispatcher = new FakeEventDispatcher;
    $useCase = new RegisterUserUseCase(
        userRepository: $repository,
        hasher: new FakeHasher,
        transactionManager: $transactionManager,
        eventDispatcher: $eventDispatcher,
    );

    $command = new RegisterUserCommand(
        name: 'John Doe',
        email: 'failure@example.com',
        plainPassword: 'Secret123!',
    );

    expect(fn (): RegisterUserResponse => $useCase->execute($command))
        ->toThrow(RuntimeException::class)
        ->and($eventDispatcher->dispatchedEvents())->toHaveCount(0)
        ->and($transactionManager->wrappedOperationCount)->toBe(1);
});
