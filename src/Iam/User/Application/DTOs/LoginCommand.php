<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $idempotencyKey = null
    ) {}
}
