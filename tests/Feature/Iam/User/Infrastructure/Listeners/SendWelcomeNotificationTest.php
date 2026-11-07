<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\WelcomeNotification;
use Src\Shared\Domain\ValueObjects\Email;

test('sends the welcome notification when a user verifies their email', function (): void {
    Notification::fake();

    $user = UserModel::factory()->create();

    Event::dispatch(new UserVerified(
        UserId::fromString($user->id),
        Email::fromString($user->email),
    ));

    Notification::assertSentTo($user, WelcomeNotification::class);
});

test('does not send the welcome notification when a user registers', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create();

    Event::dispatch(new UserRegistered(
        UserId::fromString($user->id),
        $user->name,
        Email::fromString($user->email),
        new RolesCollection([Role::USER]),
    ));

    Notification::assertNotSentTo($user, WelcomeNotification::class);
});

test('does not send the welcome notification when an unverified user changes email', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create();

    Event::dispatch(new UnverifiedUserChangedEmail(
        UserId::fromString($user->id),
        Email::fromString($user->email),
    ));

    Notification::assertNotSentTo($user, WelcomeNotification::class);
});

test('does not send the welcome notification when the user does not exist', function (): void {
    Notification::fake();

    Event::dispatch(new UserVerified(
        UserId::generate(),
        Email::fromString('missing@example.com'),
    ));

    Notification::assertNothingSent();
});
