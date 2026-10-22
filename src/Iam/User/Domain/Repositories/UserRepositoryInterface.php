<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Repositories;

use Src\Iam\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int|string $id): ?User;

    public function save(User $entity): void;
}
