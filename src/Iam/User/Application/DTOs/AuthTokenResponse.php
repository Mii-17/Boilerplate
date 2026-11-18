<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

final readonly class AuthTokenResponse
{
    public function __construct(
        public string $token,
        public string $tokenType = 'Bearer',
    ) {}
}
