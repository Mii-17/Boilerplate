<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\UseCases;

use Src\Iam\User\Application\DTOs\ChangeUnverifiedEmailCommand;
use Src\Iam\User\Application\DTOs\ChangeUnverifiedEmailResponse;
use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Exceptions\UserEmailAlreadyTakenException;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Shared\Domain\ValueObjects\Email;

final readonly class ChangeUnverifiedEmailUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ChangeUnverifiedEmailCommand $command): ChangeUnverifiedEmailResponse
    {
        $userId = UserId::fromString($command->id);

        $user = $this->userRepository->findById($userId);

        if (! $user instanceof User) {
            throw new UserNotFoundException($command->id);
        }

        $newEmail = Email::fromString($command->newEmail);
        $existingUser = $this->userRepository->findByEmail($newEmail);

        if ($existingUser instanceof User && ! $existingUser->id()->equals($user->id())) {
            throw new UserEmailAlreadyTakenException($newEmail->value());
        }

        $this->transactionManager->transaction(function () use ($user, $newEmail): void {
            $user->changeUnverifiedEmail($newEmail);

            $this->userRepository->save($user);
        });

        $events = $user->pullDomainEvents();

        $this->eventDispatcher->dispatchAll($events);

        return ChangeUnverifiedEmailResponse::fromEntity($user);
    }
}
