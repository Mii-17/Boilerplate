<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;
use Src\Iam\User\Application\UseCases\LoginUserUseCase;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Infrastructure\Persistence\DatabaseTransactionManager;

final class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $hash = config('iam.dummy_password_hash');

        if (! is_string($hash)) {
            throw new \InvalidArgumentException(
                'The iam.dummy_password_hash configuration must be a string.'
            );
        }

        $this->app->when(LoginUserUseCase::class)
            ->needs(PasswordHash::class)
            ->give(
                fn (): PasswordHash => PasswordHash::fromHash(
                    $hash
                )
            );

        // $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);

        $this->app->bind(
            TransactionManagerInterface::class,
            DatabaseTransactionManager::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
