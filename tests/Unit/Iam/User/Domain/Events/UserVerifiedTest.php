<?php

declare(strict_types=1);

use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Shared\Domain\ValueObjects\Email;

test('user verified domain event holds its payload correctly', function (): void {
    $userId = UserId::generate();
    $userEmail = Email::fromString('john@example.com');

    $event = new UserVerified($userId, $userEmail);

    expect($event->userId())->toBe($userId)
        ->and($event->email())->toBe($userEmail)
        ->and($event->occurredOn())->toBeInstanceOf(DateTimeImmutable::class);
});
