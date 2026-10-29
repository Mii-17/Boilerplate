<?php

declare(strict_types=1);

namespace Src\Shared\Application\Idempotency;

use DateTimeImmutable;

interface IdempotencyStoreInterface
{
    /**
     * Atomically reserves a key or returns its existing state.
     *
     * Expired records must be eligible for a new reservation.
     */
    public function reserve(
        string $key,
        string $fingerprint,
        DateTimeImmutable $expiresAt,
    ): IdempotencyReservation;

    public function complete(
        string $key,
        string $fingerprint,
        IdempotencyResult $result,
    ): void;

    public function release(
        string $key,
        string $fingerprint,
    ): void;
}
