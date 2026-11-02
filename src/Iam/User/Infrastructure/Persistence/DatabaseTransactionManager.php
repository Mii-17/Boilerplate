<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Persistence;

use Closure;
use Illuminate\Support\Facades\DB;
use Src\Iam\User\Application\Ports\TransactionManagerInterface;

final class DatabaseTransactionManager implements TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param  Closure(): T  $operation
     * @return T
     */
    public function transaction(Closure $operation): mixed
    {
        return DB::transaction($operation);
    }
}
