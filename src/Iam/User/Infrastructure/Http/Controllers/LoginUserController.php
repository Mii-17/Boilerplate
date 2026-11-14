<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Iam\User\Application\DTOs\LoginUserCommand;
use Src\Iam\User\Application\UseCases\LoginUserUseCase;
use Src\Iam\User\Infrastructure\Http\Requests\LoginUserRequest;
use Src\Iam\User\Infrastructure\Models\UserModel;

final class LoginUserController
{
    public function __invoke(
        LoginUserRequest $request,
        LoginUserUseCase $loginUser,
    ): JsonResponse {
        $response = $loginUser->execute(new LoginUserCommand(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        ));

        $token = UserModel::query()
            ->findOrFail($response->id)
            ->createToken('api')
            ->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
