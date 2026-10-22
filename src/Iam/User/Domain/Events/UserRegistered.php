<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Events;

use DateTimeImmutable;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Shared\Domain\Bus\Event\DomainEvent;
use Src\Shared\Domain\ValueObjects\Email;

final readonly class UserRegistered extends DomainEvent
{
    public function __construct(
        private UserId $userId,
        private string $name,
        private Email $email,
        private RolesCollection $roles,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($userId->value(), $eventId, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function roles(): RolesCollection
    {
        return $this->roles;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'name' => $this->name,
            'email' => $this->email->value(),
            'roles' => $this->roles->toArray(),
        ];
    }
}
