<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\Ports;

use Closure;

interface TransactionManagerInterface
{
    public function transaction(Closure $operation): mixed;
}
