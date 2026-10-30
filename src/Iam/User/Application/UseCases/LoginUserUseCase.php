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
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;
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

        $plainPassword = PlainPassword::fromString(
            $command->password,
        );

        $hash = $user instanceof User
            ? $user->passwordHash()
            : $this->dummyPasswordHash;

        $passwordIsValid = $this->hasher->verify($plainPassword, $hash);

        if (! $user instanceof User || ! $passwordIsValid) {
            throw new InvalidCredentialsException;
        }

        if (! $user->isVerified()) {
            throw new UserNotVerifiedException($user->email());
        }

        return LoginUserResponse::fromEntity($user);
    }
}
