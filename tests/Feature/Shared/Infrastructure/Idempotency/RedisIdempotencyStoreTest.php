<?php

declare(strict_types=1);

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Str;
use Src\Shared\Application\Idempotency\IdempotencyReservationStatus;
use Src\Shared\Application\Idempotency\IdempotencyResult;
use Src\Shared\Infrastructure\Idempotency\RedisIdempotencyStore;

/**
 * @param  Closure(RedisIdempotencyStore, string, string): void  $test
 */
function withRedisIdempotencyStore(Closure $test): void
{
    $store = new RedisIdempotencyStore(app(RedisFactory::class));
    $key = 'test-'.Str::uuid7()->toString();
    $fingerprint = hash('sha256', 'request-a');

    try {
        $test($store, $key, $fingerprint);
    } finally {
        app(RedisFactory::class)->connection()->del('idempotency:'.$key);
    }
}

test('acquires a fresh reservation', function (): void {
    withRedisIdempotencyStore(function (RedisIdempotencyStore $store, string $key, string $fingerprint): void {
        $reservation = $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        expect($reservation->status)->toBe(IdempotencyReservationStatus::ACQUIRED);
    });
});

test('replays the stored result after completion', function (): void {
    withRedisIdempotencyStore(function (RedisIdempotencyStore $store, string $key, string $fingerprint): void {
        $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));
        $store->complete($key, $fingerprint, new IdempotencyResult('registration', ['userId' => 'user-123']));

        $reservation = $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        expect($reservation->status)->toBe(IdempotencyReservationStatus::REPLAY)
            ->and($reservation->result?->type)->toBe('registration')
            ->and($reservation->result?->payload)->toBe(['userId' => 'user-123']);
    });
});

test('rejects a key reserved with a different fingerprint', function (): void {
    withRedisIdempotencyStore(function (RedisIdempotencyStore $store, string $key, string $fingerprint): void {
        $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        $reservation = $store->reserve($key, hash('sha256', 'request-b'), new DateTimeImmutable('+1 hour'));

        expect($reservation->status)->toBe(IdempotencyReservationStatus::CONFLICT);
    });
});

test('reports a reservation as in progress before completion', function (): void {
    withRedisIdempotencyStore(function (RedisIdempotencyStore $store, string $key, string $fingerprint): void {
        $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        $reservation = $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        expect($reservation->status)->toBe(IdempotencyReservationStatus::IN_PROGRESS);
    });
});

test('allows a new reservation once the ttl expires', function (): void {
    withRedisIdempotencyStore(function (RedisIdempotencyStore $store, string $key, string $fingerprint): void {
        $store->reserve($key, $fingerprint, new DateTimeImmutable('+2 seconds'));

        sleep(3);

        $reservation = $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        expect($reservation->status)->toBe(IdempotencyReservationStatus::ACQUIRED);
    });
});

test('releases a reservation so the key can be reserved again', function (): void {
    withRedisIdempotencyStore(function (RedisIdempotencyStore $store, string $key, string $fingerprint): void {
        $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        $store->release($key, $fingerprint);

        $reservation = $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        expect($reservation->status)->toBe(IdempotencyReservationStatus::ACQUIRED);
    });
});

test('release ignores a reservation with a different fingerprint', function (): void {
    withRedisIdempotencyStore(function (RedisIdempotencyStore $store, string $key, string $fingerprint): void {
        $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        $store->release($key, hash('sha256', 'request-b'));

        $reservation = $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        expect($reservation->status)->toBe(IdempotencyReservationStatus::IN_PROGRESS);
    });
});

test('refuses to complete a reservation with a different fingerprint', function (): void {
    withRedisIdempotencyStore(function (RedisIdempotencyStore $store, string $key, string $fingerprint): void {
        $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        $store->complete($key, hash('sha256', 'request-b'), new IdempotencyResult('registration', ['userId' => 'user-123']));

        $reservation = $store->reserve($key, $fingerprint, new DateTimeImmutable('+1 hour'));

        expect($reservation->status)->toBe(IdempotencyReservationStatus::IN_PROGRESS);
    });
})->throws(RuntimeException::class);
