<?php

declare(strict_types=1);

use Src\Shared\Application\Idempotency\IdempotencyService;
use Src\Shared\Application\Idempotency\IdempotencyStoreInterface;
use Src\Shared\Infrastructure\Idempotency\RedisIdempotencyStore;

test('resolves IdempotencyStoreInterface to RedisIdempotencyStore', function (): void {
    expect(app(IdempotencyStoreInterface::class))->toBeInstanceOf(RedisIdempotencyStore::class);
});

test('idempotency service is built from the redis store', function (): void {
    $service = app(IdempotencyService::class);

    $store = new ReflectionProperty($service, 'store')->getValue($service);

    expect($store)->toBeInstanceOf(RedisIdempotencyStore::class);
});
