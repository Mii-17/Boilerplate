<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Exceptions;

use DomainException;
use Src\Shared\Domain\ValueObjects\Email;

final class UserNotVerifiedException extends DomainException
{
    public function __construct(Email $email)
    {
        parent::__construct(sprintf('The user with email <%s> is not verified.', $email->value()));
    }
}
