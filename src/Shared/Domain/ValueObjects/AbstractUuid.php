<?php

declare(strict_types=1);

namespace Src\Shared\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

abstract readonly class AbstractUuid
{
    final public function __construct(protected string $value)
    {
        if (! Uuid::isValid($value)) {
            throw new InvalidArgumentException('Invalid UUID format.');
        }
    }

    public static function generate(): static
    {
        return new static(Uuid::uuid7()->toString());
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
