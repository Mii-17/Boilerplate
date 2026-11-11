<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Idempotency;

use DateTimeImmutable;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\PhpRedisConnection;
use JsonException;
use RuntimeException;
use Src\Shared\Application\Idempotency\IdempotencyReservation;
use Src\Shared\Application\Idempotency\IdempotencyResult;
use Src\Shared\Application\Idempotency\IdempotencyStoreInterface;

final readonly class RedisIdempotencyStore implements IdempotencyStoreInterface
{
    private const int MAX_RESERVATION_ATTEMPTS = 3;

    private const string COMPLETE_SCRIPT = <<<'LUA'
        local value = redis.call('GET', KEYS[1])

        if not value then
            return 0
        end

        if cjson.decode(value).fingerprint ~= ARGV[1] then
            return -1
        end

        redis.call('SET', KEYS[1], ARGV[2], 'XX', 'KEEPTTL')

        return 1
        LUA;

    private const string RELEASE_SCRIPT = <<<'LUA'
        local value = redis.call('GET', KEYS[1])

        if not value then
            return 0
        end

        if cjson.decode(value).fingerprint ~= ARGV[1] then
            return 0
        end

        return redis.call('DEL', KEYS[1])
        LUA;

    public function __construct(private RedisFactory $redis) {}

    public function reserve(
        string $key,
        string $fingerprint,
        DateTimeImmutable $expiresAt,
    ): IdempotencyReservation {
        $redisKey = $this->createKey($key);

        $ttl = $expiresAt->getTimestamp() - time();

        if ($ttl <= 0) {
            throw new RuntimeException('Idempotency expiration must be in the future.');
        }

        $json = $this->encode([
            'fingerprint' => $fingerprint,
            'result' => null,
        ]);

        for ($attempt = 0; $attempt < self::MAX_RESERVATION_ATTEMPTS; $attempt++) {
            $reserved = $this->connection()->set(
                $redisKey,
                $json,
                'EX',
                $ttl,
                'NX',
            );

            if ($reserved !== false) {
                return IdempotencyReservation::acquired();
            }

            $current = $this->connection()->get($redisKey);

            if (! is_string($current)) {
                continue;
            }

            $data = $this->decode($current);

            if ($data['fingerprint'] !== $fingerprint) {
                return IdempotencyReservation::conflict();
            }

            if ($data['result'] !== null) {
                return IdempotencyReservation::replay(
                    $this->deserializeResult($data['result']),
                );
            }

            return IdempotencyReservation::inProgress();
        }

        throw new RuntimeException('Unable to reserve the idempotency key.');
    }

    public function complete(
        string $key,
        string $fingerprint,
        IdempotencyResult $result,
    ): void {
        $json = $this->encode([
            'fingerprint' => $fingerprint,
            'result' => [
                'type' => $result->type,
                'payload' => $result->payload,
            ],
        ]);

        $completed = $this->connection()->eval(
            self::COMPLETE_SCRIPT,
            1,
            $this->createKey($key),
            $fingerprint,
            $json,
        );

        if ($completed === -1) {
            throw new RuntimeException(
                'Cannot complete an idempotency reservation with a different fingerprint.',
            );
        }
    }

    public function release(
        string $key,
        string $fingerprint,
    ): void {
        $this->connection()->eval(
            self::RELEASE_SCRIPT,
            1,
            $this->createKey($key),
            $fingerprint,
        );
    }

    private function connection(): PhpRedisConnection
    {
        $connection = $this->redis->connection();

        if (! $connection instanceof PhpRedisConnection) {
            throw new RuntimeException(
                'The idempotency store requires the phpredis client.',
            );
        }

        return $connection;
    }

    private function createKey(string $key): string
    {
        return 'idempotency:'.$key;
    }

    /**
     * @param  array{fingerprint: string, result: array{type: string, payload: array<string, mixed>}|null}  $record
     */
    private function encode(array $record): string
    {
        try {
            return json_encode($record, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Unable to encode the idempotency record.',
                0,
                $exception,
            );
        }
    }

    /**
     * @return array{fingerprint: string, result: array{type: string, payload: array<string, mixed>}|null}
     */
    private function decode(string $json): array
    {
        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Unable to decode the idempotency record.',
                0,
                $exception,
            );
        }

        if (! is_array($data) || ! is_string($data['fingerprint'] ?? null)) {
            throw new RuntimeException('Malformed idempotency record.');
        }

        $result = $data['result'] ?? null;

        if ($result !== null && (
            ! is_array($result)
            || ! is_string($result['type'] ?? null)
            || ! is_array($result['payload'] ?? null)
        )) {
            throw new RuntimeException('Malformed idempotency result.');
        }

        $payload = [];

        if ($result !== null) {
            foreach ($result['payload'] as $payloadKey => $payloadValue) {
                if (! is_string($payloadKey)) {
                    throw new RuntimeException('Malformed idempotency result payload.');
                }

                $payload[$payloadKey] = $payloadValue;
            }
        }

        return [
            'fingerprint' => $data['fingerprint'],
            'result' => $result === null ? null : [
                'type' => $result['type'],
                'payload' => $payload,
            ],
        ];
    }

    /**
     * @param  array{type: string, payload: array<string, mixed>}  $result
     */
    private function deserializeResult(array $result): IdempotencyResult
    {
        return new IdempotencyResult($result['type'], $result['payload']);
    }
}
