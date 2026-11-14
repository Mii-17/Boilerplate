<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Iam\User\Application\DTOs\ChangeUnverifiedEmailCommand;
use Src\Iam\User\Application\UseCases\ChangeUnverifiedEmailUseCase;
use Src\Iam\User\Infrastructure\Http\Requests\ChangeUnverifiedEmailRequest;
use Src\Iam\User\Infrastructure\Http\Resources\EmailChangeResource;
use Src\Iam\User\Infrastructure\Models\UserModel;

final class ChangeUnverifiedEmailController
{
    public function __invoke(
        ChangeUnverifiedEmailRequest $request,
        ChangeUnverifiedEmailUseCase $changeEmail,
    ): JsonResponse {
        $user = $request->user();
        assert($user instanceof UserModel);

        $response = $changeEmail->execute(new ChangeUnverifiedEmailCommand(
            id: $user->id,
            newEmail: $request->string('email')->toString(),
        ));

        return EmailChangeResource::make($response)->response();
    }
}
