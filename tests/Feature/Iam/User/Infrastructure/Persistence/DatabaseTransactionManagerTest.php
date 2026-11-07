<?php

declare(strict_types=1);

use Src\Iam\User\Application\Ports\TransactionManagerInterface;
use Src\Iam\User\Infrastructure\Persistence\DatabaseTransactionManager;

test('resolves TransactionManagerInterface to DatabaseTransactionManager from container', function (): void {
    expect(app(TransactionManagerInterface::class))->toBeInstanceOf(DatabaseTransactionManager::class);
});
