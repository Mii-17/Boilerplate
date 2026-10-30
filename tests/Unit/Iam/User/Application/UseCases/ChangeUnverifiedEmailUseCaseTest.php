<?php

declare(strict_types=1);

use Src\Iam\User\Application\DTOs\ChangeUnverifiedEmailCommand;
use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\UseCases\ChangeUnverifiedEmailUseCase;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Exceptions\UserAlreadyVerifiedException;
use Src\Iam\User\Domain\Exceptions\UserEmailAlreadyTakenException;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;
use Src\Shared\Domain\ValueObjects\Exceptions\InvalidEmailException;
use Tests\Doubles\Crypt\FakeHasher;
use Tests\Doubles\Events\FakeEventDispatcher;
use Tests\Doubles\Persistence\DummyTransactionManager;
use Tests\Doubles\Repositories\InMemoryUserRepository;

/**
 * @return array{
 *     0: ChangeUnverifiedEmailUseCase,
 *     1: InMemoryUserRepository,
 *     2: FakeEventDispatcher,
 *     3: RegisterUserUseCase
 * }
 */
function createChangeUnverifiedEmailUsesCasesContext(): array
{
    $repository = new InMemoryUserRepository;
    $eventDispatcher = new FakeEventDispatcher;
    $hasher = new FakeHasher;
    $transactionManager = new DummyTransactionManager;

    $changeEmailUseCase = new ChangeUnverifiedEmailUseCase(
        userRepository: $repository,
        transactionManager: $transactionManager,
        eventDispatcher: $eventDispatcher,
    );

    $registerUseCase = new RegisterUserUseCase(
        userRepository: $repository,
        hasher: $hasher,
        transactionManager: $transactionManager,
        eventDispatcher: $eventDispatcher,
    );

    return [$changeEmailUseCase, $repository, $eventDispatcher, $registerUseCase];
}

test('unverified user can change email and dispatch a domain event', function (): void {
    [$changeEmailUseCase, $repository, $eventDispatcher, $registerUseCase] = createChangeUnverifiedEmailUsesCasesContext();

    $userName = 'user';
    $userEmail = 'user@example.com';
    $userPassword = 'Secret123!';

    $registerUserCommand = new RegisterUserCommand($userName, $userEmail, $userPassword);

    $registerUseCase->execute($registerUserCommand);

    $savedUser = $repository->findByEmail(Email::fromString($userEmail));
    assert($savedUser instanceof User);

    $newEmail = Email::fromString('new@example.com');
    $changeUserEmailCommand = new ChangeUnverifiedEmailCommand($savedUser->id()->value(), $newEmail->value());

    $response = $changeEmailUseCase->execute($changeUserEmailCommand);

    $updatedUser = $repository->findByEmail(
        Email::fromString('new@example.com'),
    );

    expect($updatedUser)->toBeInstanceOf(User::class);

    expect($response->email)->toBe('new@example.com')
        ->and($eventDispatcher->hasDispatched(UnverifiedUserChangedEmail::class))->toBeTrue();
});

test('verified user can not change email', function (): void {
    [$changeEmailUseCase, $repository, $eventDispatcher, $registerUseCase] = createChangeUnverifiedEmailUsesCasesContext();

    $userName = 'user';
    $userEmail = 'user@example.com';
    $userPassword = 'Secret123!';

    $registerUserCommand = new RegisterUserCommand($userName, $userEmail, $userPassword);

    $registerUseCase->execute($registerUserCommand);

    $savedUser = $repository->findByEmail(Email::fromString($userEmail));
    assert($savedUser instanceof User);

    $token = $savedUser->verificationToken();
    assert($token instanceof VerificationToken);

    $savedUser->verify($token->value());

    $eventsBefore = count($eventDispatcher->dispatchedEvents());

    $newEmail = Email::fromString('new@example.com');
    $changeUserEmailCommand = new ChangeUnverifiedEmailCommand($savedUser->id()->value(), $newEmail->value());

    expect(fn () => $changeEmailUseCase->execute($changeUserEmailCommand))
        ->toThrow(UserAlreadyVerifiedException::class);

    expect(count($eventDispatcher->dispatchedEvents()))
        ->toBe($eventsBefore);
});

test('user can not change email to an existing email', function (): void {
    [$changeEmailUseCase, $repository, $eventDispatcher, $registerUseCase] = createChangeUnverifiedEmailUsesCasesContext();

    $registerUseCase->execute(
        new RegisterUserCommand('Existing User', 'taken@example.com', 'Secret123!')
    );

    $registerUseCase->execute(
        new RegisterUserCommand('User Two', 'user2@example.com', 'Secret123!')
    );

    $savedUser = $repository->findByEmail(Email::fromString('user2@example.com'));
    assert($savedUser instanceof User);

    $changeUserEmailCommand = new ChangeUnverifiedEmailCommand($savedUser->id()->value(), 'taken@example.com');

    $eventsBefore = count($eventDispatcher->dispatchedEvents());

    expect(fn () => $changeEmailUseCase->execute($changeUserEmailCommand))
        ->toThrow(UserEmailAlreadyTakenException::class);

    expect(count($eventDispatcher->dispatchedEvents()))
        ->toBe($eventsBefore);
});

test('user can not change email to an invalid email', function (): void {
    [$changeEmailUseCase, $repository,, $registerUseCase] = createChangeUnverifiedEmailUsesCasesContext();

    $registerUseCase->execute(
        new RegisterUserCommand('User', 'user@example.com', 'Secret123!')
    );

    $savedUser = $repository->findByEmail(Email::fromString('user@example.com'));
    assert($savedUser instanceof User);

    $changeUserEmailCommand = new ChangeUnverifiedEmailCommand($savedUser->id()->value(), 'not-an-email');

    expect(fn () => $changeEmailUseCase->execute($changeUserEmailCommand))
        ->toThrow(InvalidEmailException::class);
});

test('throws when user does not exist', function (): void {
    [$changeEmailUseCase] = createChangeUnverifiedEmailUsesCasesContext();

    $command = new ChangeUnverifiedEmailCommand(
        UserId::generate()->value(),
        'new@example.com',
    );

    expect(fn () => $changeEmailUseCase->execute($command))
        ->toThrow(UserNotFoundException::class);
});

test('allows changing to the same email', function (): void {
    [$changeEmailUseCase, $repository,, $registerUseCase] = createChangeUnverifiedEmailUsesCasesContext();

    $registerUseCase->execute(
        new RegisterUserCommand(
            'User',
            'user@example.com',
            'Secret123!',
        ),
    );

    $user = $repository->findByEmail(
        Email::fromString('user@example.com'),
    );

    assert($user instanceof User);

    $command = new ChangeUnverifiedEmailCommand(
        $user->id()->value(),
        'user@example.com',
    );

    $response = $changeEmailUseCase->execute($command);

    expect($response->email)->toBe('user@example.com');
});
