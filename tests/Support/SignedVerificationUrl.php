<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\URL;
use Src\Iam\User\Infrastructure\Models\UserModel;

final class SignedVerificationUrl
{
    public static function forUser(
        UserModel $user,
        string $token,
        ?DateTimeInterface $expiresAt = null,
    ): string {
        return self::for((string) $user->id, $token, $expiresAt);
    }

    public static function for(
        string $userId,
        string $token,
        ?DateTimeInterface $expiresAt = null,
    ): string {
        return URL::temporarySignedRoute(
            'iam.verification.verify',
            $expiresAt ?? now()->addHour(),
            ['userId' => $userId, 'token' => $token],
        );
    }
}
