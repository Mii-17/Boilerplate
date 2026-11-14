<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Iam\User\Application\DTOs\VerifyEmailCommand;
use Src\Iam\User\Application\UseCases\VerifyEmailUseCase;

final class VerifyEmailController
{
    public function __invoke(
        string $userId,
        string $token,
        VerifyEmailUseCase $verifyEmail,
    ): JsonResponse {
        $response = $verifyEmail->execute(new VerifyEmailCommand(
            userId: $userId,
            token: $token,
        ));

        return response()->json([
            'id' => $response->id,
            'email' => $response->email,
            'isVerified' => $response->isVerified,
        ]);
    }
}
