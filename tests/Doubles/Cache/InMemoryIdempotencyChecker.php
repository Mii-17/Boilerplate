<?php

declare(strict_types=1);

namespace Tests\Doubles\Cache;

use Src\Shared\Application\Ports\IdempotencyCheckerInterface;

/**
 * @template T
 *
 * @implements IdempotencyCheckerInterface<T>
 */
final class InMemoryIdempotencyChecker implements IdempotencyCheckerInterface
{
    /** @var array<string, T> */
    private array $storage = [];

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    /**
     * @return T|null
     */
    public function get(string $key): mixed
    {
        return $this->storage[$key] ?? null;
    }

    /**
     * @param  T  $value
     */
    public function set(string $key, mixed $value, int $ttlSeconds = 3600): void
    {
        $this->storage[$key] = $value;
    }

    public function clear(): void
    {
        $this->storage = [];
    }
}
