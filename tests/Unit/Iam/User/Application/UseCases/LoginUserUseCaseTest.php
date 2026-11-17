<?php

declare(strict_types=1);

use Src\Iam\User\Application\DTOs\LoginUserCommand;
use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\UseCases\LoginUserUseCase;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Exceptions\InvalidCredentialsException;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;
use Tests\Doubles\Crypt\FakeHasher;
use Tests\Doubles\Events\FakeEventDispatcher;
use Tests\Doubles\Persistence\DummyTransactionManager;
use Tests\Doubles\Repositories\InMemoryUserRepository;

/**
 * @return array{0: LoginUserUseCase, 1: InMemoryUserRepository, 2: RegisterUserUseCase}
 */
function createLoginUserUseCaseContext(): array
{
    $repository = new InMemoryUserRepository;
    $hasher = new FakeHasher;
    $dummyPasswordHash = PasswordHash::fromHash('hashed_dummy_password');

    $useCase = new LoginUserUseCase(
        userRepository: $repository,
        hasher: $hasher,
        dummyPasswordHash: $dummyPasswordHash,
    );

    $eventDispatcher = new FakeEventDispatcher;
    $transactionManager = new DummyTransactionManager;

    $registerUseCase = new RegisterUserUseCase(
        userRepository: $repository,
        hasher: $hasher,
        transactionManager: $transactionManager,
        eventDispatcher: $eventDispatcher,
    );

    return [$useCase, $repository, $registerUseCase];
}

function createUser(
    RegisterUserUseCase $registerUseCase,
    InMemoryUserRepository $repository,
    bool $verify = true,
    string $name = 'John Doe',
    string $email = 'user@example.com',
    string $password = 'Secret123!',
): User {
    $registerUseCase->execute(
        new RegisterUserCommand(
            name: $name,
            email: $email,
            plainPassword: $password,
        ),
    );

    $user = $repository->findByEmail(
        Email::fromString($email),
    );

    assert($user instanceof User);

    if ($verify) {
        $token = $user->verificationToken();

        assert($token instanceof VerificationToken);

        $user->verify($token->value());
    }

    return $user;
}

test('login works properly with valid credentials', function (): void {
    [$useCase, $repository, $registerUseCase] = createLoginUserUseCaseContext();

    $savedUser = createUser($registerUseCase, $repository);

    $email = 'user@example.com';
    $password = 'Secret123!';

    $loginCommand = new LoginUserCommand($email, $password);

    $response = $useCase->execute($loginCommand);

    expect($response->id)->toBe($savedUser->id()->value());
});

test('login fails with invalid password', function (): void {
    [$useCase, $repository, $registerUseCase] = createLoginUserUseCaseContext();

    createUser($registerUseCase, $repository);

    $email = 'user@example.com';
    $password = 'WrongSecret123!';

    $loginCommand = new LoginUserCommand($email, $password);

    expect(fn () => $useCase->execute($loginCommand))
        ->toThrow(InvalidCredentialsException::class);
});

test('login fails with invalid credentials when user does not exist', function (): void {
    [$useCase] = createLoginUserUseCaseContext();

    $email = 'user@example.com';
    $password = 'WrongSecret123!';

    $loginCommand = new LoginUserCommand($email, $password);

    expect(fn () => $useCase->execute($loginCommand))
        ->toThrow(InvalidCredentialsException::class);
});

test('login succeeds when user email is not verified', function (): void {
    [$useCase, $repository, $registerUseCase] = createLoginUserUseCaseContext();

    $savedUser = createUser($registerUseCase, $repository, false);

    $email = 'user@example.com';
    $password = 'Secret123!';

    $loginCommand = new LoginUserCommand($email, $password);

    $response = $useCase->execute($loginCommand);

    expect($response->id)->toBe($savedUser->id()->value());
});
