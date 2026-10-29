<?php

declare(strict_types=1);

use DateInterval;
use Src\Shared\Application\Idempotency\IdempotencyConflictException;
use Src\Shared\Application\Idempotency\IdempotencyInProgressException;
use Src\Shared\Application\Idempotency\IdempotencyResult;
use Src\Shared\Application\Idempotency\IdempotencyService;
use Tests\Doubles\Persistence\InMemoryIdempotencyStore;

function createIdempotencyService(): IdempotencyService
{
    return new IdempotencyService(new InMemoryIdempotencyStore);
}

test('executes a new operation and stores its result', function (): void {
    $service = createIdempotencyService();
    $executions = 0;

    $result = $service->execute(
        'key-1',
        hash('sha256', 'request-a'),
        new DateInterval('PT1H'),
        function () use (&$executions): IdempotencyResult {
            $executions++;

            return new IdempotencyResult('registration', [
                'userId' => 'user-123',
            ]);
        },
    );

    expect($executions)->toBe(1)
        ->and($result->type)->toBe('registration')
        ->and($result->payload)->toBe(['userId' => 'user-123']);
});

test('replays the stored result without executing the operation again', function (): void {
    $store = new InMemoryIdempotencyStore;
    $service = new IdempotencyService($store);
    $executions = 0;

    $operation = function () use (&$executions): IdempotencyResult {
        $executions++;

        return new IdempotencyResult('registration', [
            'userId' => 'user-123',
        ]);
    };

    $first = $service->execute(
        'key-1',
        hash('sha256', 'request-a'),
        new DateInterval('PT1H'),
        $operation,
    );

    $second = $service->execute(
        'key-1',
        hash('sha256', 'request-a'),
        new DateInterval('PT1H'),
        $operation,
    );

    expect($executions)->toBe(1)
        ->and($second->type)->toBe($first->type)
        ->and($second->payload)->toBe($first->payload);
});

test('rejects reuse of a key with a different request fingerprint', function (): void {
    $service = createIdempotencyService();

    $service->execute(
        'key-1',
        hash('sha256', 'request-a'),
        new DateInterval('PT1H'),
        fn (): IdempotencyResult => new IdempotencyResult('registration', ['userId' => 'user-123']),
    );

    expect(fn (): IdempotencyResult => $service->execute(
        'key-1',
        hash('sha256', 'request-b'),
        new DateInterval('PT1H'),
        fn (): IdempotencyResult => new IdempotencyResult('registration', ['userId' => 'user-456']),
    ))->toThrow(IdempotencyConflictException::class);
});

test('rejects a request while the same key is in progress', function (): void {
    $store = new InMemoryIdempotencyStore;

    $store->reserve(
        'key-1',
        hash('sha256', 'request-a'),
        new DateTimeImmutable('+1 hour'),
    );

    $service = new IdempotencyService($store);

    expect(fn (): IdempotencyResult => $service->execute(
        'key-1',
        hash('sha256', 'request-a'),
        new DateInterval('PT1H'),
        fn (): IdempotencyResult => new IdempotencyResult('registration', []),
    ))->toThrow(IdempotencyInProgressException::class);
});

test('releases the reservation when the operation fails', function (): void {
    $service = new IdempotencyService(new InMemoryIdempotencyStore);
    $executions = 0;

    $operation = function () use (&$executions): IdempotencyResult {
        $executions++;

        if ($executions === 1) {
            throw new RuntimeException('Temporary failure.');
        }

        return new IdempotencyResult('registration', [
            'userId' => 'user-123',
        ]);
    };

    expect(fn (): IdempotencyResult => $service->execute(
        'key-1',
        hash('sha256', 'request-a'),
        new DateInterval('PT1H'),
        $operation,
    ))->toThrow(RuntimeException::class, 'Temporary failure.');

    $result = $service->execute(
        'key-1',
        hash('sha256', 'request-a'),
        new DateInterval('PT1H'),
        $operation,
    );

    expect($executions)->toBe(2)
        ->and($result->payload)->toBe(['userId' => 'user-123']);
});

test('rejects an empty idempotency key', function (): void {
    $service = createIdempotencyService();

    expect(fn (): IdempotencyResult => $service->execute(
        ' ',
        hash('sha256', 'request-a'),
        new DateInterval('PT1H'),
        fn (): IdempotencyResult => new IdempotencyResult('registration', []),
    ))->toThrow(InvalidArgumentException::class);
});
