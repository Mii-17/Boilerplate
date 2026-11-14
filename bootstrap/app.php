<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Iam\User\Domain\Exceptions\InvalidCredentialsException;
use Src\Iam\User\Domain\Exceptions\UserAlreadyExistsException;
use Src\Iam\User\Domain\Exceptions\UserAlreadyVerifiedException;
use Src\Iam\User\Domain\Exceptions\UserEmailAlreadyTakenException;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\Exceptions\UserNotVerifiedException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidPlainPasswordException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidVerificationTokenException;
use Src\Iam\User\Infrastructure\Http\Middleware\EnsureUserHasRole;
use Src\Shared\Infrastructure\Http\Middleware\IdempotencyMiddleware;
use Src\Shared\Infrastructure\Http\Middleware\TraceCorrelationIdMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(TraceCorrelationIdMiddleware::class);

        $middleware->appendToGroup('api', [
            IdempotencyMiddleware::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

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

        $exceptions->respond(function (Response $response): Response {
            $correlationId = request()->attributes->get('correlation_id');

            if (is_string($correlationId)) {
                $response->headers->set('X-Correlation-ID', $correlationId);
            }

            return $response;
        });
    })->create();
