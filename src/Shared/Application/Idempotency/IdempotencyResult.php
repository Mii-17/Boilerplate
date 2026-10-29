<?php

declare(strict_types=1);

namespace Src\Shared\Application\Idempotency;

use InvalidArgumentException;

final readonly class IdempotencyResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $type,
        public array $payload,
    ) {
        if (trim($type) === '') {
            throw new InvalidArgumentException(
                'Result type cannot be empty.',
            );
        }
    }
}
