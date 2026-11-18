<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Iam\User\Infrastructure\Http\Controllers\ChangeUnverifiedEmailController;
use Src\Iam\User\Infrastructure\Http\Controllers\LoginUserController;
use Src\Iam\User\Infrastructure\Http\Controllers\RegisterUserController;
use Src\Iam\User\Infrastructure\Http\Controllers\ShowAuthenticatedUserController;

Route::middleware('api')
    ->prefix('api')
    ->group(function (): void {
        Route::post('/register', RegisterUserController::class)->name('iam.register');

        Route::post('/login', LoginUserController::class)
            ->middleware('throttle:auth')
            ->name('iam.login');

        Route::get('/user', ShowAuthenticatedUserController::class)
            ->middleware('auth:sanctum')
            ->name('iam.user.show');

        Route::patch('/user/email', ChangeUnverifiedEmailController::class)
            ->middleware('auth:sanctum')
            ->name('iam.email.update');
    });
