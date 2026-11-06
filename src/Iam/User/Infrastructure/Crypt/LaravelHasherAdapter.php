<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Crypt;

use Illuminate\Contracts\Hashing\Hasher;
use Src\Iam\User\Application\Ports\HasherInterface;
use Src\Iam\User\Domain\ValueObjects\PasswordAttempt;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;

final class LaravelHasherAdapter implements HasherInterface
{
    public function __construct(public Hasher $hasher) {}

    public function hash(PlainPassword $plainPassword): PasswordHash
    {
        return PasswordHash::fromHash($this->hasher->make($plainPassword->value()));
    }

    public function verify(PasswordAttempt $attempt, PasswordHash $hashedPassword): bool
    {
        return $this->hasher->check($attempt->value(), $hashedPassword->value());
    }
}
