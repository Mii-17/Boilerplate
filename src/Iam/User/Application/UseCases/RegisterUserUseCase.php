<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\UseCases;

use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\DTOs\RegisterUserResponse;
use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Iam\User\Application\Ports\HasherInterface;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Exceptions\UserAlreadyExistsException;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;
use Src\Shared\Domain\ValueObjects\Email;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private HasherInterface $hasher,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(
        RegisterUserCommand $command,
    ): RegisterUserResponse {
        $email = Email::fromString($command->email);

        if ($this->userRepository->existsByEmail($email)) {
            throw new UserAlreadyExistsException($email);
        }

        $passwordHash = $this->hasher->hash(
            PlainPassword::fromString($command->plainPassword),
        );

        $user = User::register(
            name: $command->name,
            email: $email,
            passwordHash: $passwordHash,
        );

        $this->transactionManager->transaction(
            function () use ($user): void {
                $this->userRepository->save($user);
            },
        );

        $this->eventDispatcher->dispatchAll(
            $user->pullDomainEvents(),
        );

        return RegisterUserResponse::fromEntity($user);
    }
}
