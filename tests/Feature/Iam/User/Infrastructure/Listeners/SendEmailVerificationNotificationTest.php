<?php

declare(strict_types=1);

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
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

function assertVerificationEmailSentOnDemandTo(string $email): void
{
    Notification::assertSentOnDemand(
        VerifyEmailNotification::class,
        fn (VerifyEmailNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $channels === ['mail']
            && $notifiable->routes['mail'] === $email,
    );
}

function assertVerificationEmailNotSentOnDemand(): void
{
    Notification::assertNotSentTo(new AnonymousNotifiable, VerifyEmailNotification::class);
}

function spyLogger(): MockInterface
{
    $logger = Mockery::spy('log');

    Log::swap($logger);

    return $logger;
}

function assertVerificationSkipLogged(
    MockInterface $logger,
    string $userId,
    string $reason,
): void {
    $logger->shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Verification email skipped'
            && $context['user_id'] === $userId
            && $context['reason'] === $reason);
}

test('sends the verification notification when a user registers', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create();

    dispatchUserRegistered($user);

    assertVerificationEmailSentOnDemandTo($user->email);
});

test('sends the verification notification when an unverified user changes email', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create();

    Event::dispatch(new UnverifiedUserChangedEmail(
        UserId::fromString($user->id),
        Email::fromString($user->email),
    ));

    assertVerificationEmailSentOnDemandTo($user->email);
});

test('skips and logs when the user is already verified', function (): void {
    $logger = spyLogger();
    Notification::fake();

    $user = UserModel::factory()->create();

    dispatchUserRegistered($user);

    assertVerificationEmailNotSentOnDemand();
    assertVerificationSkipLogged($logger, $user->id, 'already_verified');
});

test('skips and logs when the verification token is missing', function (): void {
    $logger = spyLogger();
    Notification::fake();

    $user = UserModel::factory()->unverified()->create([
        'verification_token' => null,
    ]);

    dispatchUserRegistered($user);

    assertVerificationEmailNotSentOnDemand();
    assertVerificationSkipLogged($logger, $user->id, 'missing_token');
});

test('skips and logs when the verification token has expired', function (): void {
    $logger = spyLogger();
    Notification::fake();

    $user = UserModel::factory()->unverified()->create([
        'verification_expires_at' => now()->subMinute(),
    ]);

    dispatchUserRegistered($user);

    assertVerificationEmailNotSentOnDemand();
    assertVerificationSkipLogged($logger, $user->id, 'expired_token');
});

test('skips and logs when the user does not exist', function (): void {
    $logger = spyLogger();
    Notification::fake();

    $userId = UserId::generate();

    Event::dispatch(new UnverifiedUserChangedEmail(
        $userId,
        Email::fromString('missing@example.com'),
    ));

    assertVerificationEmailNotSentOnDemand();
    assertVerificationSkipLogged($logger, $userId->value(), 'user_not_found');
});
