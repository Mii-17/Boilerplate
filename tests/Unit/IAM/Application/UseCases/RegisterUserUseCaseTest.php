<?php

declare(strict_types=1);

use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Exceptions\UserAlreadyExistsException;
use Src\Shared\Domain\ValueObjects\Email;
use Tests\Doubles\Crypt\FakeHasher;
use Tests\Doubles\Events\FakeEventDispatcher;
use Tests\Doubles\Persistence\DummyTransactionManager;
use Tests\Doubles\Repositories\InMemoryUserRepository;

/**
 * @return array{0: RegisterUserUseCase, 1: InMemoryUserRepository, 2: FakeEventDispatcher}
 */
function createRegisterUserUseCaseContext(): array
{
    $repository = new InMemoryUserRepository;
    $eventDispatcher = new FakeEventDispatcher;
    $hasher = new FakeHasher;
    $transactionManager = new DummyTransactionManager;

    $useCase = new RegisterUserUseCase(
        userRepository: $repository,
        hasher: $hasher,
        eventDispatcher: $eventDispatcher,
        transactionManager: $transactionManager
    );

    return [$useCase, $repository, $eventDispatcher];
}

test('registers a user successfully and dispatches a domain event', function (): void {
    [$useCase, $repository, $eventDispatcher] = createRegisterUserUseCaseContext();

    $name = 'John Doe';
    $email = 'user@example.com';
    $userPassword = 'Secret123!';
    $userRoles = [Role::USER->value];

    $command = new RegisterUserCommand(
        name: $name,
        email: $email,
        plainPassword: $userPassword,
        roles: $userRoles
    );

    $useCase->execute($command);

    $savedUser = $repository->findByEmail(Email::fromString($email));

    expect($savedUser)->toBeInstanceOf(User::class);
    assert($savedUser instanceof User);

    expect($savedUser->name())->toBe($name)
        ->and($savedUser->hasRole(Role::USER))->toBeTrue()
        ->and($eventDispatcher->dispatchedEvents())->toHaveCount(1)
        ->and($eventDispatcher->hasDispatched(UserRegistered::class))->toBeTrue();
});

test('throws exception when email is already registered', function (): void {
    [$useCase, $repository] = createRegisterUserUseCaseContext();

    $email = 'user@example.com';
    $userPassword = 'Secret123!';
    $userRoles = [Role::USER->value];

    $command1 = new RegisterUserCommand(
        name: 'User One',
        email: $email,
        plainPassword: $userPassword,
        roles: $userRoles
    );

    $command2 = new RegisterUserCommand(
        name: 'User Two',
        email: $email,
        plainPassword: $userPassword,
        roles: $userRoles
    );

    $useCase->execute($command1);

    expect(fn () => $useCase->execute($command2))
        ->toThrow(UserAlreadyExistsException::class);
});
