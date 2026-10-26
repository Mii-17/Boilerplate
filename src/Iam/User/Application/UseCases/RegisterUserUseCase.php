<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\UseCases;

use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Iam\User\Application\Ports\HasherInterface;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Exceptions\UserAlreadyExistsException;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
        private HasherInterface $hasher,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function execute(RegisterUserCommand $command): User
    {
        $email = Email::fromString($command->email);

        if ($this->userRepository->existsByEmail($email)) {
            throw new UserAlreadyExistsException($email);
        }

        $userId = UserId::generate();
        $passwordHash = $this->hasher->hash(PlainPassword::fromString($command->plainPassword));
        $verificationToken = VerificationToken::generate();

        $rolesArray = array_map(
            Role::from(...),
            $command->roles
        );
        $rolesCollection = new RolesCollection($rolesArray);

        $user = User::register(
            id: $userId,
            name: $command->name,
            email: $email,
            passwordHash: $passwordHash,
            verificationToken: $verificationToken,
            roles: $rolesCollection
        );

        $this->transactionManager->transaction(function () use ($user): void {
            $this->userRepository->save($user);
            $this->eventDispatcher->dispatchAll($user->pullDomainEvents());
        });

        return $user;
    }
}
