<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Listeners;

use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\WelcomeNotification;

final class SendWelcomeNotification
{
    public function handle(UserVerified $event): void
    {
        $user = UserModel::query()->find($event->userId()->value());

        if (! $user instanceof UserModel) {
            return;
        }

        $user->notify(new WelcomeNotification($user->name));
    }
}
