<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

final readonly class UserResponseData
{
    /**
     * @param  string[]  $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public array $roles,
        public bool $isVerified
    ) {}
}
