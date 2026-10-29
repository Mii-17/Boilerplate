<?php

declare(strict_types=1);

namespace Src\Shared\Application\Idempotency;

enum IdempotencyReservationStatus
{
    case ACQUIRED;
    case REPLAY;
    case CONFLICT;
    case IN_PROGRESS;
}
