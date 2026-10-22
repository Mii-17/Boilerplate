<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects\Exceptions;

use DomainException;

final class InvalidPasswordHashException extends DomainException
{
    public static function becauseHashIsEmpty(): self
    {
        return new self('Password hash cannot be empty.');
    }
}
