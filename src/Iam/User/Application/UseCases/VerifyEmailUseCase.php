<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\UseCases;

use Src\Iam\User\Application\DTOs\VerifyEmailCommand;
use Src\Iam\User\Application\DTOs\VerifyEmailResponse;
use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\UserId;

final readonly class VerifyEmailUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(VerifyEmailCommand $command): VerifyEmailResponse
    {
        $userId = UserId::fromString($command->userId);

        $user = $this->userRepository->findById($userId);

        if (! $user instanceof User) {
            throw new UserNotFoundException($command->userId);
        }

        $this->transactionManager->transaction(function () use ($user, $command): void {
            $user->verify($command->token);

            $this->userRepository->save($user);
        });

        $events = $user->pullDomainEvents();

        $this->eventDispatcher->dispatchAll($events);

        return VerifyEmailResponse::fromEntity($user);
    }
}
