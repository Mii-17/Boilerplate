<?php

declare(strict_types=1);

namespace Tests\Doubles\Cache;

final class InMemoryIdempotencyChecker
{
    /**
     * @var array<string, array{value: mixed, expiresAt: int}>
     */
    private array $storage = [];

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function get(string $key): mixed
    {
        if (! isset($this->storage[$key])) {
            return null;
        }

        if ($this->storage[$key]['expiresAt'] <= time()) {
            unset($this->storage[$key]);

            return null;
        }

        return $this->storage[$key]['value'];
    }

    public function set(
        string $key,
        mixed $value,
        int $ttlSeconds = 3600,
    ): void {
        if ($ttlSeconds <= 0) {
            unset($this->storage[$key]);

            return;
        }

        $this->storage[$key] = [
            'value' => $value,
            'expiresAt' => time() + $ttlSeconds,
        ];
    }

    public function clear(): void
    {
        $this->storage = [];
    }
}
