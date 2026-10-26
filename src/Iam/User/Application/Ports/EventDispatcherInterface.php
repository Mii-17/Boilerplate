<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\Ports;

use Src\Shared\Domain\Bus\Event\DomainEventInterface;

interface EventDispatcherInterface
{
    /**
     * @param  array<DomainEventInterface>  $events
     */
    public function dispatchAll(array $events): void;
}
