<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Src\Iam\User\Infrastructure\Notifications\WelcomeNotification;

test('sends the notification through mail', function (): void {
    $notification = new WelcomeNotification('User');

    expect($notification->via(new stdClass))
        ->toBe(['mail']);
});

test('implements ShouldQueueAfterCommit', function (): void {
    $notification = new WelcomeNotification('User');

    expect($notification)
        ->toBeInstanceOf(ShouldQueueAfterCommit::class);
});

test('creates a welcome mail message without a verification token', function (): void {
    $notification = new WelcomeNotification('User');

    $mail = $notification->toMail(new stdClass);

    $appName = config('app.name');

    if (! is_string($appName)) {
        throw new RuntimeException('app.name debe ser un string');
    }

    expect($mail)
        ->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe('Bienvenido a '.$appName)
        ->and($mail->markdown)->toBe('mail.iam.welcome')
        ->and($mail->viewData['userName'])->toBe('User')
        ->and($mail->viewData)->not->toHaveKey('token')
        ->and($mail->viewData)->not->toHaveKey('url')
        ->and($mail->viewData)->not->toHaveKey('expiresAt');
});

test('renders the welcome view', function (): void {
    $mail = new WelcomeNotification('User')->toMail(new stdClass);

    $html = (string) $mail->render();

    expect($html)
        ->toContain('Hola, User')
        ->toContain(config('app.name'))
        ->toContain(config('app.url'));
});
