<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Exceptions;

use DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The credentials provided are invalid.');
    }
}
