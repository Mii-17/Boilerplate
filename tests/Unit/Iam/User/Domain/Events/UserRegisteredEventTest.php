<?php

declare(strict_types=1);

use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Shared\Domain\ValueObjects\Email;

test('user registered domain event holds its payload correctly', function (): void {
    $userId = UserId::generate();
    $userName = 'John Doe';
    $userEmail = Email::fromString('john@example.com');
    $userRole = new RolesCollection([Role::USER]);

    $event = new UserRegistered($userId, $userName, $userEmail, $userRole);

    expect($event->userId())->toBe($userId)
        ->and($event->name())->toBe($userName)
        ->and($event->email())->toBe($userEmail)
        ->and($event->roles())->toBe($userRole)
        ->and($event->occurredOn())->toBeInstanceOf(DateTimeImmutable::class);
});
