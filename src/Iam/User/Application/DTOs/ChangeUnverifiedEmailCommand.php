<?php

declare(strict_types=1);

namespace Src\Iam\User\Application\DTOs;

final readonly class ChangeUnverifiedEmailCommand
{
    public function __construct(
        public string $id,
        public string $newEmail,
    ) {}
}
