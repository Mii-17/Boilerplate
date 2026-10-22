<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects\Exceptions;

use DomainException;

final class InvalidRolesCollectionException extends DomainException
{
    public static function becauseItIsEmpty(): self
    {
        return new self('Roles collection cannot be empty.');
    }
}
