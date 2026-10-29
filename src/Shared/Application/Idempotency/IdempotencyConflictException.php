<?php

declare(strict_types=1);

namespace Src\Shared\Application\Idempotency;

use RuntimeException;

final class IdempotencyConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The idempotency key was reused with a different request.',
        );
    }
}
