<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

use Src\Iam\User\Domain\Entities\User;

final readonly class RegisterUserResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            name: $user->name(),
            email: $user->email()->value(),
        );
    }
}
