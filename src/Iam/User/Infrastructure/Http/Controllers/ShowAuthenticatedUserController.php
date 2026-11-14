<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Iam\User\Infrastructure\Models\UserModel;

final class ShowAuthenticatedUserController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        assert($user instanceof UserModel);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
