<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    |
    | How long a completed idempotent operation is remembered in Redis so a
    | replayed request with the same Idempotency-Key returns the stored
    | response instead of executing the operation again.
    |
    */

    'ttl' => env('IDEMPOTENCY_TTL', 86400),

];
