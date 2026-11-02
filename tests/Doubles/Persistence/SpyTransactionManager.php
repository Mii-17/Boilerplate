<?php

declare(strict_types=1);

namespace Tests\Doubles\Persistence;

use Closure;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;

final class SpyTransactionManager implements TransactionManagerInterface
{
    public int $wrappedOperationCount = 0;

    /**
     * @var array<mixed>
     */
    public array $operations = [];

    public function transaction(Closure $operation): mixed
    {
        $this->wrappedOperationCount++;
        $this->operations[] = $operation;

        return $operation();
    }
}
