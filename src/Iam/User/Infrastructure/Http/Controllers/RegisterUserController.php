<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Infrastructure\Http\Requests\RegisterUserRequest;
use Src\Iam\User\Infrastructure\Http\Resources\UserResource;

final class RegisterUserController
{
    public function __invoke(
        RegisterUserRequest $request,
        RegisterUserUseCase $registerUser,
    ): JsonResponse {
        $response = $registerUser->execute(new RegisterUserCommand(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            plainPassword: $request->string('password')->toString(),
        ));

        return UserResource::make($response)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
