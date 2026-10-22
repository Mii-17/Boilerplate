<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects;

use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidRolesCollectionException;

final readonly class RolesCollection
{
    /**
     * @var array<Role>
     */
    private array $roles;

    /**
     * @param  array<Role>  $roles
     */
    public function __construct(array $roles)
    {
        if ($roles === []) {
            throw InvalidRolesCollectionException::becauseItIsEmpty();
        }

        $uniqueRoles = [];
        foreach ($roles as $role) {
            $uniqueRoles[$role->value] = $role;
        }

        $this->roles = array_values($uniqueRoles);
    }

    /**
     * @return array<Role>
     */
    public function all(): array
    {
        return $this->roles;
    }

    public function has(Role $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->has(Role::ADMIN);
    }

    /**
     * @return array<string>
     */
    public function toArray(): array
    {
        return array_map(fn (Role $role): string => $role->value, $this->roles);
    }
}
