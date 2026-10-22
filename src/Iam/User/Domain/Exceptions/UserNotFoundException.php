<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Exceptions;

use Exception;

final class UserNotFoundException extends Exception
{
    public static function withId(int|string $id): self
    {
        return new self("User with id {$id} not found.");
    }
}
