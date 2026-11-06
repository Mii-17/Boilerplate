<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Bus;

use Illuminate\Contracts\Events\Dispatcher;
use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Shared\Domain\Bus\Event\DomainEventInterface;

final class LaravelEventDispatcherAdapter implements EventDispatcherInterface
{
    public function __construct(public Dispatcher $dispatcher) {}

    /**
     * @param  array<DomainEventInterface>  $events
     */
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
