<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\Ports;

use Src\Iam\User\Domain\ValueObjects\PasswordAttempt;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;

interface HasherInterface
{
    public function hash(PlainPassword $plainPassword): PasswordHash;

    public function verify(PasswordAttempt $attempt, PasswordHash $hashedPassword): bool;
}
