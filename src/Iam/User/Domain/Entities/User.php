<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Entities;

final readonly class User
{
    public function __construct(
        public int|string $id,
        // TODO: Add domain properties here
    ) {}
}
