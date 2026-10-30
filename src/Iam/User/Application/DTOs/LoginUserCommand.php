<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

final readonly class LoginUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
