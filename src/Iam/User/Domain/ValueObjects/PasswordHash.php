<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects;

use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidPasswordHashException;
use Stringable;

final readonly class PasswordHash implements Stringable
{
    private function __construct(private string $value) {}

    public static function fromPlainText(PlainPassword $plainPassword): self
    {
        $hash = password_hash($plainPassword->value(), PASSWORD_ARGON2ID);

        return new self($hash);
    }

    public static function fromHash(string $hash): self
    {
        if (trim($hash) === '') {
            throw InvalidPasswordHashException::becauseHashIsEmpty();
        }

        return new self($hash);
    }

    public function verify(PlainPassword $plainPassword): bool
    {
        return password_verify($plainPassword->value(), $this->value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
