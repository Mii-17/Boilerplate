<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

use Src\Iam\User\Domain\Entities\User;

final readonly class VerifyEmailResponse
{
    public function __construct(
        public string $id,
        public string $email,
        public bool $isVerified,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            email: $user->email()->value(),
            isVerified: $user->isVerified(),
        );
    }
}
