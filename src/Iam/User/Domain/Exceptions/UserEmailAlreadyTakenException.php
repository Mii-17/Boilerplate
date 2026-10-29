<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Exceptions;

use DomainException;

final class UserEmailAlreadyTakenException extends DomainException
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('User email <%s> is already taken.', $email));
    }
}
