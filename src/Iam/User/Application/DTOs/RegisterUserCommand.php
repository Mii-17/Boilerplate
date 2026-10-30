<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $plainPassword,
    ) {}
}
