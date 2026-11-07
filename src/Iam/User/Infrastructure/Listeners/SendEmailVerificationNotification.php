<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Listeners;

use Src\Iam\User\Domain\Events\UnverifiedUserChangedEmail;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\VerifyEmailNotification;

final class SendEmailVerificationNotification
{
    public function handle(UserRegistered|UnverifiedUserChangedEmail $event): void
    {
        $user = UserModel::query()->find($event->userId()->value());

        if (! $user instanceof UserModel) {
            return;
        }

        if ($user->verified_at !== null) {
            return;
        }

        $token = $user->verification_token;

        if (! is_string($token) || $token === '') {
            return;
        }

        $expiresAt = $user->verification_expires_at;

        if ($expiresAt === null || $expiresAt->isPast()) {
            return;
        }

        $user->notify(new VerifyEmailNotification(
            UserId::fromString($user->id),
            $token,
            $expiresAt->toDateTimeImmutable(),
            $user->name,
        ));
    }
}
