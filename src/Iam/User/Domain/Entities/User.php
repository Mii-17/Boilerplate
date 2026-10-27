<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Entities;

use DomainException;
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
        private bool $isVerified = false,
        private ?VerificationToken $verificationToken = null
    ) {}

    public static function register(
        UserId $id,
        string $name,
        Email $email,
        PasswordHash $passwordHash,
        VerificationToken $verificationToken,
        RolesCollection $roles = new RolesCollection([Role::USER])
    ): self {
        $user = new self($id, $name, $email, $passwordHash, $roles, false, $verificationToken);

        $user->recordThat(new UserRegistered(
            userId: $id,
            name: $name,
            email: $email,
            roles: $roles
        ));

        return $user;
    }

    public function verify(VerificationToken $providedToken): void
    {
        if ($this->isVerified) {
            throw new UserAlreadyVerifiedException($this->id);
        }

        if (! $this->verificationToken instanceof VerificationToken || ! $this->verificationToken->equals($providedToken)) {
            throw InvalidVerificationTokenException::becauseInvalid();
        }

        if ($this->verificationToken->isExpired()) {
            throw InvalidVerificationTokenException::becauseExpired();
        }

        $this->isVerified = true;
        $this->verificationToken = null;

        $this->recordThat(new UserVerified(
            userId: $this->id,
            email: $this->email
        ));
    }

    public function changeUnverifiedEmail(Email $newEmail): void
    {
        if ($this->isVerified) {
            throw new DomainException('Cannot change email of a verified user directly.');
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
        return $this->isVerified;
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
