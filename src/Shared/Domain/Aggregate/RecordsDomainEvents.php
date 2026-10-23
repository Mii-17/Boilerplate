<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Aggregate;

use Src\Shared\Domain\Bus\Event\DomainEventInterface;

trait RecordsDomainEvents
{
    /** @var array<DomainEventInterface> */
    private array $domainEvents = [];

    protected function recordThat(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return array<DomainEventInterface> */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
