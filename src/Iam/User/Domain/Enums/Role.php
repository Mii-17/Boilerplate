<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Enums;

enum Role: string
{
    case USER = 'user';
    case ADMIN = 'admin';

    public function isUser(): bool
    {
        return $this === self::USER;
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
