<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

final readonly class VerifyEmailCommand
{
    public function __construct(
        public string $userId,
        public string $token,
    ) {}
}
