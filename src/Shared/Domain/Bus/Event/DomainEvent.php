<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Bus\Event;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

abstract readonly class DomainEvent implements DomainEventInterface
{
    protected string $eventId;

    protected DateTimeImmutable $occurredOn;

    public function __construct(
        protected string $aggregateId,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        $this->eventId = $eventId ?? Uuid::uuid7()->toString();
        $this->occurredOn = $occurredOn ?? new DateTimeImmutable;
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
