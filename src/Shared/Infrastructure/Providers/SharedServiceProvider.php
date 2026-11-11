<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Shared\Application\Idempotency\IdempotencyStoreInterface;
use Src\Shared\Infrastructure\Idempotency\RedisIdempotencyStore;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            IdempotencyStoreInterface::class,
            RedisIdempotencyStore::class,
        );
    }
}
