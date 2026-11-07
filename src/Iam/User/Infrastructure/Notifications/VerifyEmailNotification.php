<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Notifications;

use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Src\Iam\User\Domain\ValueObjects\UserId;

final class VerifyEmailNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        private readonly UserId $userId,
        private readonly string $token,
        private readonly DateTimeImmutable $expiresAt,
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
        $verificationUrl = URL::temporarySignedRoute(
            'iam.verification.verify',
            $this->expiresAt,
            [
                'userId' => $this->userId->value(),
                'token' => $this->token,
            ],
        );

        return (new MailMessage)
            ->subject('Verifica tu dirección de correo')
            ->markdown('mail.iam.verify-email', [
                'userName' => $this->userName,
                'url' => $verificationUrl,
                'expiresAt' => $this->expiresAt,
            ]);
    }
}
