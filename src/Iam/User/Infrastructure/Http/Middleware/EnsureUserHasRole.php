<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Infrastructure\Models\UserRoleModel;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

final class EnsureUserHasRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles,
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $user->loadMissing('roles');

        $userRoles = $user->roles
            ->map(fn (UserRoleModel $userRole) => $userRole->role)
            ->all();

        if ($userRoles === []) {
            return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        $rolesCollection = new RolesCollection($userRoles);

        if ($roles === []) {
            throw new InvalidArgumentException('Middleware requires at least one role.');
        }

        try {
            $allowedRoles = array_map(
                Role::from(...),
                $roles,
            );
        } catch (ValueError $exception) {
            throw new InvalidArgumentException('Invalid role provided to middleware.', $exception->getCode(), previous: $exception);
        }

        foreach ($allowedRoles as $role) {
            if ($rolesCollection->has($role)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
    }
}
