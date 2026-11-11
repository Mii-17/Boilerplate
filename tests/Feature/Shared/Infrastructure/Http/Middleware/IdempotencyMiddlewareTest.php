<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Src\Shared\Application\Idempotency\IdempotencyReservation;
use Src\Shared\Application\Idempotency\IdempotencyResult;
use Src\Shared\Application\Idempotency\IdempotencyStoreInterface;
use Src\Shared\Infrastructure\Http\Middleware\IdempotencyMiddleware;
use Tests\Doubles\Persistence\InMemoryIdempotencyStore;

use function Pest\Laravel\instance;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

final class IdempotencyMiddlewareCalls
{
    public static int $executions = 0;
}

beforeEach(function (): void {
    IdempotencyMiddlewareCalls::$executions = 0;

    instance(IdempotencyStoreInterface::class, new InMemoryIdempotencyStore);

    Route::middleware(IdempotencyMiddleware::class)->group(function (): void {
        Route::post('/_test/idempotent', function (): JsonResponse {
            IdempotencyMiddlewareCalls::$executions++;

            return response()->json(['executions' => IdempotencyMiddlewareCalls::$executions], 201);
        });

        Route::patch('/_test/idempotent', function (): JsonResponse {
            IdempotencyMiddlewareCalls::$executions++;

            return response()->json(['executions' => IdempotencyMiddlewareCalls::$executions], 200);
        });

        Route::put('/_test/idempotent', function (): JsonResponse {
            IdempotencyMiddlewareCalls::$executions++;

            return response()->json(['executions' => IdempotencyMiddlewareCalls::$executions], 200);
        });

        Route::get('/_test/idempotent', function (): JsonResponse {
            IdempotencyMiddlewareCalls::$executions++;

            return response()->json(['executions' => IdempotencyMiddlewareCalls::$executions], 200);
        });

        Route::post('/_test/idempotent/flaky', function (): JsonResponse {
            IdempotencyMiddlewareCalls::$executions++;

            if (IdempotencyMiddlewareCalls::$executions === 1) {
                return response()->json(['error' => 'failure'], 500);
            }

            return response()->json(['executions' => IdempotencyMiddlewareCalls::$executions], 201);
        });
    });
});

test('replays the stored response without re-executing the operation', function (): void {
    $first = postJson('/_test/idempotent', ['name' => 'mirian'], ['Idempotency-Key' => 'key-1']);
    $second = postJson('/_test/idempotent', ['name' => 'mirian'], ['Idempotency-Key' => 'key-1']);

    $first->assertCreated();
    $second->assertCreated();

    expect(IdempotencyMiddlewareCalls::$executions)->toBe(1)
        ->and($second->json())->toBe($first->json())
        ->and($second->headers->get('Idempotency-Key'))->toBe('key-1');
});

test('responds with 409 when the key is reused with a different request', function (): void {
    postJson('/_test/idempotent', ['name' => 'mirian'], ['Idempotency-Key' => 'key-1'])
        ->assertCreated();

    postJson('/_test/idempotent', ['name' => 'other'], ['Idempotency-Key' => 'key-1'])
        ->assertStatus(409);
});

test('responds with 425 while the same key is still in progress', function (): void {
    instance(IdempotencyStoreInterface::class, new class implements IdempotencyStoreInterface
    {
        public function reserve(
            string $key,
            string $fingerprint,
            DateTimeImmutable $expiresAt,
        ): IdempotencyReservation {
            return IdempotencyReservation::inProgress();
        }

        public function complete(
            string $key,
            string $fingerprint,
            IdempotencyResult $result,
        ): void {}

        public function release(string $key, string $fingerprint): void {}
    });

    postJson('/_test/idempotent', ['name' => 'mirian'], ['Idempotency-Key' => 'key-1'])
        ->assertStatus(425);
});

test('does not apply to requests without the idempotency key', function (): void {
    postJson('/_test/idempotent', ['name' => 'mirian'])->assertCreated();
    postJson('/_test/idempotent', ['name' => 'mirian'])->assertCreated();

    expect(IdempotencyMiddlewareCalls::$executions)->toBe(2);
});

test('does not apply to idempotent http methods', function (): void {
    withHeader('Idempotency-Key', 'key-1')->postJson('/_test/idempotent', ['name' => 'mirian'])->assertCreated();

    withHeader('Idempotency-Key', 'key-1')->putJson('/_test/idempotent', ['name' => 'mirian'])->assertOk();
    withHeader('Idempotency-Key', 'key-1')->putJson('/_test/idempotent', ['name' => 'mirian'])->assertOk();

    withHeader('Idempotency-Key', 'key-1')->getJson('/_test/idempotent')->assertOk();
    withHeader('Idempotency-Key', 'key-1')->getJson('/_test/idempotent')->assertOk();

    expect(IdempotencyMiddlewareCalls::$executions)->toBe(5);
});

test('applies to patch requests', function (): void {
    patchJson('/_test/idempotent', ['name' => 'mirian'], ['Idempotency-Key' => 'key-1'])->assertOk();
    patchJson('/_test/idempotent', ['name' => 'mirian'], ['Idempotency-Key' => 'key-1'])->assertOk();

    expect(IdempotencyMiddlewareCalls::$executions)->toBe(1);
});

test('releases the reservation when the operation fails so it can be retried', function (): void {
    $first = postJson('/_test/idempotent/flaky', [], ['Idempotency-Key' => 'key-1']);
    $second = postJson('/_test/idempotent/flaky', [], ['Idempotency-Key' => 'key-1']);

    $first->assertStatus(500);
    $second->assertCreated();

    expect(IdempotencyMiddlewareCalls::$executions)->toBe(2);
});
