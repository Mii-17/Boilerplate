<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Iam\User\Application\Ports\HasherInterface;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;
use Src\Iam\User\Application\UseCases\LoginUserUseCase;
use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Infrastructure\Bus\LaravelEventDispatcherAdapter;
use Src\Iam\User\Infrastructure\Crypt\LaravelHasherAdapter;
use Src\Iam\User\Infrastructure\Listeners\SendEmailVerificationNotification;
use Src\Iam\User\Infrastructure\Listeners\SendWelcomeNotification;
use Src\Iam\User\Infrastructure\Persistence\DatabaseTransactionManager;
use Src\Iam\User\Infrastructure\Persistence\EloquentUserRepository;

final class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(LoginUserUseCase::class)
            ->needs(PasswordHash::class)
            ->give(
                fn (): PasswordHash => PasswordHash::fromHash(
                    config()->string('iam.dummy_password_hash')
                )
            );

        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);

        $this->app->bind(
            TransactionManagerInterface::class,
            DatabaseTransactionManager::class,
        );

        $this->app->bind(
            HasherInterface::class,
            LaravelHasherAdapter::class,
        );
        $this->app->bind(
            EventDispatcherInterface::class,
            LaravelEventDispatcherAdapter::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        Event::listen([UserRegistered::class, UnverifiedUserChangedEmail::class], SendEmailVerificationNotification::class);
        Event::listen(UserRegistered::class, SendWelcomeNotification::class);
    }
}
