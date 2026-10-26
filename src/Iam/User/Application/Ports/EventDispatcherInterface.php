<?php

declare(strict_types=1);

namespace App\Application\Common\Ports;

interface EventDispatcherInterface
{
    /**
     * @param  array<object>  $events
     */
    public function dispatchAll(array $events): void;
}
