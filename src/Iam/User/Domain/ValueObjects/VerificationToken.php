<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\ValueObjects;

use DateTimeImmutable;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidVerificationTokenException;
use Stringable;

final readonly class VerificationToken implements Stringable
{
    public function __construct(
        private string $value,
        private DateTimeImmutable $expiresAt
    ) {
        if (trim($value) === '') {
            throw InvalidVerificationTokenException::becauseTokenIsEmpty();
        }
    }

    public static function generate(int $hoursValidFor = 24): self
    {
        return new self(
            value: bin2hex(random_bytes(32)),
            expiresAt: new DateTimeImmutable("+{$hoursValidFor} hours")
        );
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        $currentDateTime = $now ?? new DateTimeImmutable;

        return $currentDateTime > $this->expiresAt;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
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
