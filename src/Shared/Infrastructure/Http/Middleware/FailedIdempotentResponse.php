<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;

final class FailedIdempotentResponse extends \RuntimeException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct('The idempotent operation returned an error response.');
    }
}
