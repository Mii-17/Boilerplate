<?php

declare(strict_types=1);

use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Shared\Domain\ValueObjects\Email;

test('unregistered user changed email domain event holds its payload correctly', function (): void {
    $userId = UserId::generate();
    $userEmail = Email::fromString('john@example.com');

    $event = new UnverifiedUserChangedEmail($userId, $userEmail);

    expect($event->userId())->toBe($userId)
        ->and($event->email())->toBe($userEmail)
        ->and($event->occurredOn())->toBeInstanceOf(DateTimeImmutable::class);
});
