<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Bus\Event;

use DateTimeImmutable;

interface DomainEventInterface
{
    public function eventId(): string;

    public function aggregateId(): string;

    public function occurredOn(): DateTimeImmutable;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
