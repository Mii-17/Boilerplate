<?php

declare(strict_types=1);

namespace Src\Shared\Application\Ports;

/**
 * @template T
 */
interface IdempotencyCheckerInterface
{
    public function has(string $key): bool;

    /**
     * @return T|null
     */
    public function get(string $key): mixed;

    /**
     * @param  T  $value
     */
    public function set(string $key, mixed $value, int $ttlSeconds = 3600): void;
}
