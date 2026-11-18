<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class HealthCheckController
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->databaseIsUp(),
            'redis' => $this->redisIsUp(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'error',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function databaseIsUp(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function redisIsUp(): bool
    {
        try {
            return (bool) Redis::ping();
        } catch (Throwable) {
            return false;
        }
    }
}
