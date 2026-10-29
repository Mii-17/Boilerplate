<?php

declare(strict_types=1);

namespace Src\Shared\Application\Idempotency;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final readonly class IdempotencyService
{
    public function __construct(
        private IdempotencyStoreInterface $store,
    ) {}

    /**
     * @param  callable(): IdempotencyResult  $operation
     */
    public function execute(
        string $key,
        string $fingerprint,
        DateInterval $ttl,
        callable $operation,
    ): IdempotencyResult {
        if (trim($key) === '' || trim($fingerprint) === '') {
            throw new InvalidArgumentException(
                'Idempotency key and fingerprint cannot be empty.',
            );
        }

        $now = new DateTimeImmutable;
        $expiresAt = $now->add($ttl);

        if ($expiresAt <= $now) {
            throw new InvalidArgumentException(
                'Idempotency TTL must be positive.',
            );
        }

        $reservation = $this->store->reserve(
            $key,
            $fingerprint,
            $expiresAt,
        );

        if ($reservation->status === IdempotencyReservationStatus::REPLAY) {
            return $reservation->result
                ?? throw new \LogicException('Replay has no stored result.');
        }

        if ($reservation->status === IdempotencyReservationStatus::CONFLICT) {
            throw new IdempotencyConflictException;
        }

        if ($reservation->status === IdempotencyReservationStatus::IN_PROGRESS) {
            throw new IdempotencyInProgressException;
        }

        try {
            $result = $operation();
        } catch (Throwable $exception) {
            $this->store->release($key, $fingerprint);

            throw $exception;
        }

        $this->store->complete($key, $fingerprint, $result);

        return $result;
    }
}
