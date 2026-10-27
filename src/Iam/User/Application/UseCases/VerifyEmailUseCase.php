<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\UseCases;

use DateTimeImmutable;
use Src\Iam\User\Application\DTOs\VerifyEmailCommand;
use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Application\Ports\IdempotencyCheckerInterface;

final readonly class VerifyEmailUseCase
{
    /**
     * @param  IdempotencyCheckerInterface<User>  $idempotencyChecker
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
        private TransactionManagerInterface $transactionManager,
        private IdempotencyCheckerInterface $idempotencyChecker,
    ) {}

    public function execute(VerifyEmailCommand $command): User
    {

        if ($command->idempotencyKey !== null) {
            if ($this->idempotencyChecker->has($command->idempotencyKey)) {
                $cachedUser = $this->idempotencyChecker->get($command->idempotencyKey);
                assert($cachedUser instanceof User);

                return $cachedUser;
            }
        }

        $user = $this->userRepository->findById(UserId::fromString($command->userId));

        if (! $user instanceof User) {
            throw new UserNotFoundException($command->userId);
        }

        $this->transactionManager->transaction(function () use ($user, $command): void {
            $expiresAt = new DateTimeImmutable('+24 hours');
            $user->verify(new VerificationToken($command->token, $expiresAt));
            $this->eventDispatcher->dispatchAll($user->pullDomainEvents());
        });

        if ($command->idempotencyKey !== null) {
            $this->idempotencyChecker->set($command->idempotencyKey, $user);
        }

        return $user;
    }
}
