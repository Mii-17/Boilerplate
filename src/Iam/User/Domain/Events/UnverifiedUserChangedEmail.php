<?php

declare(strict_types=1);

namespace Src\Iam\User\Domain\Events;

use DateTimeImmutable;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Shared\Domain\Bus\Event\DomainEvent;
use Src\Shared\Domain\ValueObjects\Email;

final readonly class UnverifiedUserChangedEmail extends DomainEvent
{
    public function __construct(
        private UserId $userId,
        private Email $email,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($userId->value(), $eventId, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'email' => $this->email->value(),
        ];
    }
}
