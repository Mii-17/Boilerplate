<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\VerifyEmailNotification;
use Src\Shared\Domain\ValueObjects\Email;

function dispatchUserRegistered(UserModel $user): void
{
    Event::dispatch(new UserRegistered(
        UserId::fromString($user->id),
        $user->name,
        Email::fromString($user->email),
        new RolesCollection([Role::USER]),
    ));
}

test('sends the verification notification when a user registers', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create();

    dispatchUserRegistered($user);

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('sends the verification notification when an unverified user changes email', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create();

    Event::dispatch(new UnverifiedUserChangedEmail(
        UserId::fromString($user->id),
        Email::fromString($user->email),
    ));

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('does not send the notification when the user is already verified', function (): void {
    Notification::fake();

    $user = UserModel::factory()->create();

    dispatchUserRegistered($user);

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

test('does not send the notification when the verification token is missing', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create([
        'verification_token' => null,
    ]);

    dispatchUserRegistered($user);

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

test('does not send the notification when the verification token has expired', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create([
        'verification_expires_at' => now()->subMinute(),
    ]);

    dispatchUserRegistered($user);

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

test('does not send the notification when the user does not exist', function (): void {
    Notification::fake();

    Event::dispatch(new UnverifiedUserChangedEmail(
        UserId::generate(),
        Email::fromString('missing@example.com'),
    ));

    Notification::assertNothingSent();
});
