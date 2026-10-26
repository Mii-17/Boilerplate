<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

final readonly class AuthResponseData
{
    public function __construct(
        public string $token,
        public UserResponseData $user,
    ) {}
}
