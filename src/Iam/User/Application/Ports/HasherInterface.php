<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\Ports;

use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;

interface HasherInterface
{
    public function hash(PlainPassword $plainPassword): PasswordHash;

    public function verify(PlainPassword $plainPassword, PasswordHash $hashedPassword): bool;
}
