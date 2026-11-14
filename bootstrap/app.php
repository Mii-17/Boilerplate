<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Src\Iam\User\Infrastructure\Http\Middleware\EnsureUserHasRole;
use Src\Shared\Infrastructure\Http\DomainExceptionMapper;
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

        (new DomainExceptionMapper)->register($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response): Response {
            $correlationId = request()->attributes->get('correlation_id');

            if (is_string($correlationId)) {
                $response->headers->set('X-Correlation-ID', $correlationId);
            }

            return $response;
        });
    })->create();
