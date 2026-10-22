<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Exceptions;

use DomainException;
use Src\Iam\User\Domain\ValueObjects\UserId;

final class UserAlreadyVerifiedException extends DomainException
{
    public function __construct(UserId $userId)
    {
        parent::__construct(sprintf('The user with id <%s> is already verified.', $userId->value()));
    }
}
