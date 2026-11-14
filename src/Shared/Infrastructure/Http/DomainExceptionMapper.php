<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Iam\User\Domain\Exceptions\InvalidCredentialsException;
use Src\Iam\User\Domain\Exceptions\UserAlreadyExistsException;
use Src\Iam\User\Domain\Exceptions\UserAlreadyVerifiedException;
use Src\Iam\User\Domain\Exceptions\UserEmailAlreadyTakenException;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\Exceptions\UserNotVerifiedException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidPlainPasswordException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidVerificationTokenException;

final class DomainExceptionMapper
{
    public function register(Exceptions $exceptions): void
    {
        $exceptions->renderable(fn (UserAlreadyExistsException $exception): JsonResponse => response()->json(
            ['message' => $exception->getMessage()],
            Response::HTTP_CONFLICT,
        ));

        $exceptions->renderable(fn (InvalidPlainPasswordException $exception): JsonResponse => response()->json([
            'message' => $exception->getMessage(),
            'errors' => ['password' => [$exception->getMessage()]],
        ], Response::HTTP_UNPROCESSABLE_ENTITY));

        $exceptions->renderable(fn (InvalidCredentialsException $exception): JsonResponse => response()->json(
            ['message' => $exception->getMessage()],
            Response::HTTP_UNAUTHORIZED,
        ));

        $exceptions->renderable(fn (UserNotVerifiedException $exception): JsonResponse => response()->json(
            ['message' => $exception->getMessage()],
            Response::HTTP_FORBIDDEN,
        ));

        $exceptions->renderable(fn (UserNotFoundException $exception): JsonResponse => response()->json(
            ['message' => $exception->getMessage()],
            Response::HTTP_NOT_FOUND,
        ));

        $exceptions->renderable(fn (InvalidVerificationTokenException $exception): JsonResponse => response()->json(
            ['message' => $exception->getMessage()],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ));

        $exceptions->renderable(fn (UserAlreadyVerifiedException $exception): JsonResponse => response()->json(
            ['message' => $exception->getMessage()],
            Response::HTTP_CONFLICT,
        ));

        $exceptions->renderable(fn (UserEmailAlreadyTakenException $exception): JsonResponse => response()->json(
            ['message' => $exception->getMessage()],
            Response::HTTP_CONFLICT,
        ));
    }
}
