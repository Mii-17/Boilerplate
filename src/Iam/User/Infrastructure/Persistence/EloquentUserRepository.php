<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Shared\Domain\ValueObjects\Email;

final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserMapper $mapper,
    ) {}

    public function findById(UserId $id): ?User
    {
        $user = UserModel::query()
            ->with('roles')
            ->find($id->value());

        return $user instanceof UserModel ? $this->mapper->toDomain($user) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $user = UserModel::query()
            ->with('roles')
            ->where('email', $email->value())
            ->first();

        return $user instanceof UserModel ? $this->mapper->toDomain($user) : null;
    }

    public function findByVerificationToken(VerificationToken $token): ?User
    {
        $user = UserModel::query()
            ->with('roles')
            ->where('verification_token', $token->value())
            ->first();

        return $user instanceof UserModel ? $this->mapper->toDomain($user) : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return UserModel::query()
            ->where('email', $email->value())
            ->exists();
    }

    public function save(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $model = UserModel::query()->updateOrCreate(
                ['id' => $user->id()->value()],
                $this->mapper->toPersistence($user),
            );

            $model->roles()->delete();

            foreach ($user->roles()->all() as $role) {
                $model->roles()->create(['role' => $role]);
            }
        });
    }
}
