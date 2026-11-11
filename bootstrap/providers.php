<?php

declare(strict_types=1);

use Src\Iam\User\Infrastructure\Providers\UserServiceProvider;
use Src\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    UserServiceProvider::class,
    SharedServiceProvider::class,
];
