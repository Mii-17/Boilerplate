<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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

    public function boot(): void
    {
        Scramble::configure()->routes(
            fn (Route $route): bool => Str::startsWith($route->uri, 'api/')
                || $route->getName() === 'iam.verification.verify',
        );
    }
}
