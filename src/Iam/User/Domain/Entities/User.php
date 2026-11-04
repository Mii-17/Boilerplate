<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Entities;

use DateTimeImmutable;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Domain\Exceptions\UserAlreadyVerifiedException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidVerificationTokenException;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Shared\Domain\Aggregate\RecordsDomainEvents;
use Src\Shared\Domain\ValueObjects\Email;

class User
{
    use RecordsDomainEvents;

    private function __construct(
        private UserId $id,
        private string $name,
        private Email $email,
        private PasswordHash $passwordHash,
        private RolesCollection $roles,
        private ?DateTimeImmutable $verifiedAt = null,
        private ?VerificationToken $verificationToken = null
    ) {}

    public static function register(
        string $name,
        Email $email,
        PasswordHash $passwordHash,
        RolesCollection $roles = new RolesCollection([Role::USER]),
        ?UserId $id = null,
        ?VerificationToken $verificationToken = null,
    ): self {
        $id ??= UserId::generate();
        $verificationToken ??= VerificationToken::generate();

        $user = new self($id, $name, $email, $passwordHash, $roles, null, $verificationToken);

        $user->recordThat(new UserRegistered(
            userId: $id,
            name: $name,
            email: $email,
            roles: $roles
        ));

        return $user;
    }

    public static function reconstitute(
        UserId $id,
        string $name,
        Email $email,
        PasswordHash $passwordHash,
        RolesCollection $roles,
        ?DateTimeImmutable $verifiedAt,
        ?VerificationToken $verificationToken,
    ): self {
        return new self($id, $name, $email, $passwordHash, $roles, $verifiedAt, $verificationToken);
    }

    public function verify(string $providedToken): void
    {
        if ($this->isVerified()) {
            throw new UserAlreadyVerifiedException($this->id);
        }

        if (! $this->verificationToken instanceof VerificationToken) {
            throw InvalidVerificationTokenException::becauseInvalid();
        }

        if ($this->verificationToken->value() !== $providedToken) {
            throw InvalidVerificationTokenException::becauseInvalid();
        }

        if ($this->verificationToken->isExpired()) {
            throw InvalidVerificationTokenException::becauseExpired();
        }

        $this->verifiedAt = new DateTimeImmutable;
        $this->verificationToken = null;

        $this->recordThat(new UserVerified(
            userId: $this->id,
            email: $this->email,
        ));
    }

    public function changeUnverifiedEmail(Email $newEmail): void
    {
        if ($this->isVerified()) {
            throw new UserAlreadyVerifiedException($this->id);
        }

        $this->email = $newEmail;

        $this->recordThat(new UnverifiedUserChangedEmail(
            userId: $this->id,
            email: $this->email
        ));
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function isVerified(): bool
    {
        return $this->verifiedAt instanceof DateTimeImmutable;
    }

    public function verifiedAt(): ?DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function verificationToken(): ?VerificationToken
    {
        return $this->verificationToken;
    }

    public function roles(): RolesCollection
    {
        return $this->roles;
    }

    public function hasRole(Role $role): bool
    {
        return $this->roles->has($role);
    }
}
