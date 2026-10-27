<?php

declare(strict_types=1);

namespace Tests\Doubles\Repositories;

use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, string> */
    private array $emailIndex = [];

    /** @var array<string, string> */
    private array $tokenIndex = [];

    public function findById(UserId $id): ?User
    {
        return $this->users[$id->value()] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        $userId = $this->emailIndex[$email->value()] ?? null;

        return $userId !== null ? ($this->users[$userId] ?? null) : null;
    }

    public function findByVerificationToken(VerificationToken $token): ?User
    {
        $userId = $this->tokenIndex[$token->value()] ?? null;

        return $userId !== null ? ($this->users[$userId] ?? null) : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return isset($this->emailIndex[$email->value()]);
    }

    public function save(User $user): void
    {
        $userId = $user->id()->value();

        foreach ($this->emailIndex as $emailStr => $id) {
            if ($id === $userId) {
                unset($this->emailIndex[$emailStr]);
            }
        }
        foreach ($this->tokenIndex as $tokenStr => $id) {
            if ($id === $userId) {
                unset($this->tokenIndex[$tokenStr]);
            }
        }

        $this->users[$userId] = $user;

        $this->emailIndex[$user->email()->value()] = $userId;

        $token = $user->verificationToken();
        if ($token instanceof VerificationToken) {
            $this->tokenIndex[$token->value()] = $userId;
        }
    }

    public function count(): int
    {
        return count($this->users);
    }
}
