<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\UseCases;

use Src\Iam\User\Application\DTOs\LoginUserCommand;
use Src\Iam\User\Application\DTOs\LoginUserResponse;
use Src\Iam\User\Application\Ports\HasherInterface;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Exceptions\InvalidCredentialsException;
use Src\Iam\User\Domain\Exceptions\UserNotVerifiedException;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\PasswordAttempt;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Shared\Domain\ValueObjects\Email;

final readonly class LoginUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private HasherInterface $hasher,
        private PasswordHash $dummyPasswordHash,
    ) {}

    public function execute(
        LoginUserCommand $command,
    ): LoginUserResponse {
        $email = Email::fromString($command->email);

        $user = $this->userRepository->findByEmail($email);

        $hash = $user instanceof User
            ? $user->passwordHash()
            : $this->dummyPasswordHash;

        $attempt = PasswordAttempt::fromString($command->password);

        $valid = $this->hasher->verify($attempt, $hash);
        if (! $user instanceof User || ! $valid) {
            throw new InvalidCredentialsException;
        }

        if (! $user->isVerified()) {
            throw new UserNotVerifiedException($user->email());
        }

        return LoginUserResponse::fromEntity($user);
    }
}
