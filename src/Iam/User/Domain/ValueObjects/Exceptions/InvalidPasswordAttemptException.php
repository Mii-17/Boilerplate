<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects\Exceptions;

use DomainException;

final class InvalidPasswordAttemptException extends DomainException
{
    public static function becauseLengthIsInvalid(): self
    {
        return new self('Password is too long.');
    }
}
