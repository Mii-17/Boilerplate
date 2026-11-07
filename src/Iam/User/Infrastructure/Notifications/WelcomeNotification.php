<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use RuntimeException;

final class WelcomeNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        private readonly string $userName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        if (! is_string($appName)) {
            throw new RuntimeException('app.name debe ser un string');
        }

        return (new MailMessage)
            ->subject('Bienvenido a '.$appName)
            ->markdown('mail.iam.welcome', [
                'userName' => $this->userName,
            ]);
    }
}
