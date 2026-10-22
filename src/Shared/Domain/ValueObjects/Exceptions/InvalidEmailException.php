<?php

declare(strict_types=1);

namespace Src\Shared\Domain\ValueObjects\Exceptions;

use DomainException;

final class InvalidEmailException extends DomainException
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('Invalid format for <%s>.', $email));
    }
}
