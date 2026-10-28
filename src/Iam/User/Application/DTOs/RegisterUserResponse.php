<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Enums\Role;

final readonly class RegisterUserResponse
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public array $roles,
        public bool $isVerified,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            name: $user->name(),
            email: $user->email()->value(),
            roles: array_values(
                array_map(
                    static fn (Role $role): string => $role->value,
                    $user->roles()->all(),
                ),
            ),
            isVerified: $user->isVerified(),
        );
    }
}
