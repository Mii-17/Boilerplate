<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Infrastructure\Notifications\VerifyEmailNotification;

test('sends the notification through mail', function (): void {
    $notification = new VerifyEmailNotification(
        UserId::fromString('01925a8c-1b3a-7d9e-8f2a-123456789abc'),
        'verification-token',
        new DateTimeImmutable('+24 hours'),
        'User',
    );

    expect($notification->via(new stdClass))
        ->toBe(['mail']);
});

test('implements ShouldQueueAfterCommit', function (): void {
    $notification = new VerifyEmailNotification(
        UserId::fromString('01925a8c-1b3a-7d9e-8f2a-123456789abc'),
        'verification-token',
        new DateTimeImmutable('+24 hours'),
        'User',
    );

    expect($notification)
        ->toBeInstanceOf(ShouldQueueAfterCommit::class);
});

test('creates a mail message with the expected subject and view', function (): void {
    $notification = new VerifyEmailNotification(
        UserId::fromString('01925a8c-1b3a-7d9e-8f2a-123456789abc'),
        'verification-token',
        new DateTimeImmutable('+24 hours'),
        'User',
    );

    $mail = $notification->toMail(new stdClass);

    expect($mail)
        ->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe('Verifica tu dirección de correo')
        ->and($mail->markdown)->toBe('mail.iam.verify-email')
        ->and($mail->viewData['userName'])->toBe('User')
        ->and($mail->viewData['expiresAt'])->toBeInstanceOf(DateTimeImmutable::class);
});

test('generates a valid signed verification URL', function (): void {
    $expiresAt = new DateTimeImmutable('+24 hours');

    $notification = new VerifyEmailNotification(
        UserId::fromString('01925a8c-1b3a-7d9e-8f2a-123456789abc'),
        'verification-token',
        $expiresAt,
        'User',
    );

    $mail = $notification->toMail(new stdClass);
    $url = $mail->viewData['url'];

    expect(URL::hasValidSignature(Request::create($url)))->toBeTrue();

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($query['expires'])->toBe((string) $expiresAt->getTimestamp())
        ->and($query['signature'])->not->toBeEmpty();
});
