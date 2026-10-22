<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Repositories;

use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\ValueObjects\Email;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function findByVerificationToken(VerificationToken $token): ?User;

    public function existsByEmail(Email $email): bool;

    public function save(User $user): void;
}
