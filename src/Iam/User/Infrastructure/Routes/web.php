<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Iam\User\Infrastructure\Http\Controllers\VerifyEmailController;

Route::middleware(['web', 'throttle:verification', 'signed'])
    ->get('/iam/email/verify/{userId}/{token}', VerifyEmailController::class)
    ->name('iam.verification.verify');
