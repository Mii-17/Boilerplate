<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects;

use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidPlainPasswordException;

final readonly class PlainPassword implements \Stringable
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $length = mb_strlen($value);
        if ($length < 8 || $length > 100) {
            throw InvalidPlainPasswordException::becauseLengthIsInvalid();
        }

        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/';

        if (preg_match($pattern, $value) !== 1) {
            throw InvalidPlainPasswordException::becauseLengthIsInvalid();
        }

        return new self($value);
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
