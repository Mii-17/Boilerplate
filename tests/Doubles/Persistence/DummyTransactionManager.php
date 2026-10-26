<?php

declare(strict_types=1);

namespace Tests\Doubles\Persistence;

use Closure;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;

final class DummyTransactionManager implements TransactionManagerInterface
{
    public function transaction(Closure $operation): mixed
    {
        return $operation();
    }
}
