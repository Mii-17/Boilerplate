<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Infrastructure\Http\Requests\RegisterUserRequest;

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

        return response()->json([
            'id' => $response->id,
            'name' => $response->name,
            'email' => $response->email,
        ], 201);
    }
}
