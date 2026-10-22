<?php

declare(strict_types=1);

namespace Src\Shared\Domain\ValueObjects;

use Src\Shared\Domain\ValueObjects\Exceptions\InvalidEmailException;

final readonly class Email implements \Stringable
{
    private function __construct(
        private string $value
    ) {}

    public static function fromString(string $originalEmail): self
    {
        $email = strtolower(trim($originalEmail));

        if (
            strlen($email) > 254
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw InvalidEmailException::withEmail($originalEmail);
        }

        return new self($email);
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
