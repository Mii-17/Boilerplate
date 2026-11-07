<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Src\Iam\User\Domain\Entities\User;
use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Repositories\UserRepositoryInterface;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Iam\User\Infrastructure\Notifications\VerifyEmailNotification;

final readonly class SendEmailVerificationNotification
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function handle(UserRegistered|UnverifiedUserChangedEmail $event): void
    {
        $user = $this->users->findById($event->userId());

        if (! $user instanceof User) {
            $this->logSkip($event, 'user_not_found');

            return;
        }

        if ($user->isVerified()) {
            $this->logSkip($event, 'already_verified');

            return;
        }

        $token = $user->verificationToken();

        if (! $token instanceof VerificationToken) {
            $this->logSkip($event, 'missing_token');

            return;
        }

        if ($token->isExpired()) {
            $this->logSkip($event, 'expired_token');

            return;
        }

        Notification::route('mail', $user->email()->value())
            ->notify(new VerifyEmailNotification(
                $user->id(),
                $token->value(),
                $token->expiresAt(),
                $user->name(),
            ));
    }

    private function logSkip(UserRegistered|UnverifiedUserChangedEmail $event, string $reason): void
    {
        Log::warning('Verification email skipped', [
            'user_id' => $event->userId()->value(),
            'reason' => $reason,
        ]);
    }
}
