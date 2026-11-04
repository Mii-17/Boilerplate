<?php

declare(strict_types=1);

namespace Tests\Doubles\Crypt;

use Src\Iam\User\Application\Ports\HasherInterface;
use Src\Iam\User\Domain\ValueObjects\PasswordAttempt;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;

final class FakeHasher implements HasherInterface
{
    public function hash(PlainPassword $password): PasswordHash
    {
        return PasswordHash::fromHash('hashed_'.$password->value());
    }

    public function verify(PasswordAttempt $attempt, PasswordHash $hash): bool
    {
        return $hash->value() === 'hashed_'.$attempt->value();
    }
}
