<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\Ports;

use Src\Shared\Domain\Bus\Event\DomainEvent;

interface EventDispatcherInterface
{
    /**
     * @param  array<DomainEvent>  $events
     */
    public function dispatchAll(array $events): void;
}
