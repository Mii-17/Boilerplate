<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'signed'])
    ->get('/iam/email/verify/{userId}/{token}', fn () => response()->noContent())
    ->name('iam.verification.verify');
