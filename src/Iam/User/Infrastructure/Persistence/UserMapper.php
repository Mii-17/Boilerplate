<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Persistence;

use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Models\UserRoleModel;
use Src\Shared\Domain\ValueObjects\Email;

final class UserMapper
{
    public function toDomain(UserModel $model): User
    {
        $verificationToken = null;

        if ($model->verification_token !== null && $model->verification_expires_at !== null) {
            $verificationToken = new VerificationToken(
                $model->verification_token,
                $model->verification_expires_at->toDateTimeImmutable()
            );
        }
        /* @param list<Role> */
        $roles = $model->roles
            ->map(fn (UserRoleModel $role): Role => $role->role)
            ->all();

        return User::reconstitute(
            UserId::fromString($model->id),
            $model->name,
            Email::fromString($model->email),
            PasswordHash::fromHash($model->password),
            new RolesCollection($roles),
            $model->verified_at?->toDateTimeImmutable(),
            $verificationToken
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistence(User $user): array
    {
        return [
            'id' => $user->id()->value(),
            'name' => $user->name(),
            'email' => $user->email()->value(),
            'password' => $user->passwordHash()->value(),
            'verified_at' => $user->verifiedAt(),
            'verification_token' => $user->verificationToken()?->value(),
            'verification_expires_at' => $user->verificationToken()?->expiresAt(),
        ];
    }
}
