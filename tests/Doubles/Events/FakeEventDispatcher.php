<?php

declare(strict_types=1);

namespace Tests\Doubles\Events;

use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Shared\Domain\Bus\Event\DomainEvent;

final class FakeEventDispatcher implements EventDispatcherInterface
{
    /** @var list<DomainEvent> */
    private array $dispatchedEvents = [];

    /**
     * @param  list<DomainEvent>  $events
     */
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatchedEvents[] = $event;
        }
    }

    /**
     * @return list<DomainEvent>
     */
    public function dispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    public function hasDispatched(string $eventClass): bool
    {
        return array_any($this->dispatchedEvents, fn (DomainEvent $event): bool => $event instanceof $eventClass);
    }
}
