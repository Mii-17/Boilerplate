<?php

declare(strict_types=1);

namespace Src\Shared\Application\Idempotency;

final readonly class IdempotencyReservation
{
    private function __construct(
        public IdempotencyReservationStatus $status,
        public ?IdempotencyResult $result = null,
    ) {}

    public static function acquired(): self
    {
        return new self(IdempotencyReservationStatus::ACQUIRED);
    }

    public static function replay(IdempotencyResult $result): self
    {
        return new self(IdempotencyReservationStatus::REPLAY, $result);
    }

    public static function conflict(): self
    {
        return new self(IdempotencyReservationStatus::CONFLICT);
    }

    public static function inProgress(): self
    {
        return new self(IdempotencyReservationStatus::IN_PROGRESS);
    }
}
