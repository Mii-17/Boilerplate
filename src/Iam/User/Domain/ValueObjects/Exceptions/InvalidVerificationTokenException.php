<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects\Exceptions;

use DomainException;

final class InvalidVerificationTokenException extends DomainException
{
    public static function becauseTokenIsEmpty(): self
    {
        return new self('Verification token cannot be empty.');
    }

    public static function becauseInvalid(): self
    {
        return new self('Invalid verification token.');
    }

    public static function becauseExpired(): self
    {
        return new self('Verification token has expired.');
    }
}
