<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects;

use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidPasswordAttemptException;
use Stringable;

final readonly class PasswordAttempt implements Stringable
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if (mb_strlen($value) > 255) {
            throw InvalidPasswordAttemptException::becauseLengthIsInvalid();
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
