<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects\Exceptions;

use DomainException;

final class InvalidPlainPasswordException extends DomainException
{
    public static function becauseLengthIsInvalid(): self
    {
        return new self('The password must be between 8 and 100 characters long.');
    }

    public static function becauseComplexityIsMissing(): self
    {
        return new self('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.');
    }
}
