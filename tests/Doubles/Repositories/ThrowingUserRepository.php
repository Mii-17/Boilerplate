<?php

declare(strict_types=1);

namespace Tests\Doubles\Repositories;

use RuntimeException;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;

final class ThrowingUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User
    {
        return null;
    }

    public function findByEmail(Email $email): ?User
    {
        return null;
    }

    public function findByVerificationToken(VerificationToken $token): ?User
    {
        return null;
    }

    public function existsByEmail(Email $email): bool
    {
        return false;
    }

    public function save(User $user): void
    {
        throw new RuntimeException('simulated save failure');
    }
}
