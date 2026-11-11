<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use DateInterval;
use Illuminate\Http\Request;
use Src\Shared\Application\Idempotency\IdempotencyConflictException;
use Src\Shared\Application\Idempotency\IdempotencyInProgressException;
use Src\Shared\Application\Idempotency\IdempotencyResult;
use Src\Shared\Application\Idempotency\IdempotencyService;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyMiddleware
{
    private const string KEY_HEADER = 'Idempotency-Key';

    private const array GUARDED_METHODS = ['POST', 'PATCH'];

    public function __construct(private IdempotencyService $idempotency) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header(self::KEY_HEADER);

        if (! $this->shouldApply($request, $key)) {
            return $next($request);
        }

        assert(is_string($key));

        try {
            $result = $this->idempotency->execute(
                $key,
                $this->fingerprint($request),
                $this->ttl(),
                fn (): IdempotencyResult => $this->executeOperation($next, $request),
            );
        } catch (IdempotencyConflictException) {
            return response()->json(
                ['message' => 'The idempotency key was already used with a different request.'],
                Response::HTTP_CONFLICT,
            );
        } catch (IdempotencyInProgressException) {
            return response()->json(
                ['message' => 'A request with the same idempotency key is still in progress.'],
                425,
            );
        } catch (FailedIdempotentResponse $exception) {
            return $exception->response;
        }

        $response = $this->responseFromResult($result);
        $response->headers->set(self::KEY_HEADER, $key);

        return $response;
    }

    private function executeOperation(Closure $next, Request $request): IdempotencyResult
    {
        $response = $next($request);

        if (! $response->isSuccessful()) {
            throw new FailedIdempotentResponse($response);
        }

        return new IdempotencyResult('response', [
            'status' => $response->getStatusCode(),
            'content_type' => $response->headers->get('Content-Type'),
            'body' => (string) $response->getContent(),
        ]);
    }

    private function shouldApply(Request $request, ?string $key): bool
    {
        return in_array($request->method(), self::GUARDED_METHODS, true)
            && is_string($key)
            && trim($key) !== '';
    }

    private function fingerprint(Request $request): string
    {
        $identifier = $request->user()?->getAuthIdentifier() ?? $request->ip();

        return hash(
            'sha256',
            $request->method()
                .'|'.$request->path()
                .'|'.$request->getContent()
                .'|'.(is_string($identifier) || is_int($identifier) ? (string) $identifier : ''),
        );
    }

    private function ttl(): DateInterval
    {
        $seconds = config('idempotency.ttl');

        return new DateInterval('PT'.(is_numeric($seconds) ? (string) (int) $seconds : '86400').'S');
    }

    private function responseFromResult(IdempotencyResult $result): Response
    {
        $payload = $result->payload;

        $status = $payload['status'] ?? 200;
        $body = $payload['body'] ?? '';
        $contentType = $payload['content_type'] ?? null;

        $response = response(
            is_string($body) ? $body : '',
            is_int($status) ? $status : 200,
        );

        if (is_string($contentType)) {
            $response->headers->set('Content-Type', $contentType);
        }

        return $response;
    }
}
