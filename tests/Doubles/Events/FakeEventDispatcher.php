<?php

declare(strict_types=1);

namespace Tests\Doubles\Events;

use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Shared\Domain\Bus\Event\DomainEventInterface;

final class FakeEventDispatcher implements EventDispatcherInterface
{
    /** @var list<DomainEventInterface> */
    private array $dispatchedEvents = [];

    /**
     * @param  list<DomainEventInterface>  $events
     */
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatchedEvents[] = $event;
        }
    }

    /**
     * @return list<DomainEventInterface>
     */
    public function dispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    public function hasDispatched(string $eventClass): bool
    {
        return array_any($this->dispatchedEvents, fn (DomainEventInterface $event): bool => $event instanceof $eventClass);
    }
}
