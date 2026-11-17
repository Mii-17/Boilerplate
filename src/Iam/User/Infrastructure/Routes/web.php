<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Iam\User\Infrastructure\Http\Controllers\ChangeUnverifiedEmailWebController;
use Src\Iam\User\Infrastructure\Http\Controllers\LoginUserWebController;
use Src\Iam\User\Infrastructure\Http\Controllers\LogoutUserWebController;
use Src\Iam\User\Infrastructure\Http\Controllers\RegisterUserWebController;
use Src\Iam\User\Infrastructure\Http\Controllers\ShowChangeEmailFormController;
use Src\Iam\User\Infrastructure\Http\Controllers\ShowLoginFormController;
use Src\Iam\User\Infrastructure\Http\Controllers\ShowRegisterFormController;
use Src\Iam\User\Infrastructure\Http\Controllers\ShowVerificationNoticeController;
use Src\Iam\User\Infrastructure\Http\Controllers\VerifyEmailController;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/register', ShowRegisterFormController::class)->name('register');
        Route::post('/register', RegisterUserWebController::class);

        Route::get('/login', ShowLoginFormController::class)->name('login');
        Route::post('/login', LoginUserWebController::class)->middleware('throttle:auth');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', LogoutUserWebController::class)->name('logout');

        Route::get('/email/verify', ShowVerificationNoticeController::class)->name('verification.notice');
        Route::get('/email/change', ShowChangeEmailFormController::class)->name('email.change');
        Route::patch('/email', ChangeUnverifiedEmailWebController::class)->name('email.update');
    });

    Route::middleware(['throttle:verification', 'signed'])
        ->get('/iam/email/verify/{userId}/{token}', VerifyEmailController::class)
        ->name('iam.verification.verify');
});
