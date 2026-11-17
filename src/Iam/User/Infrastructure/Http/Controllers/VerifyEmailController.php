<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Src\Iam\User\Application\DTOs\VerifyEmailCommand;
use Src\Iam\User\Application\UseCases\VerifyEmailUseCase;
use Src\Iam\User\Domain\Exceptions\UserAlreadyVerifiedException;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidVerificationTokenException;
use Src\Iam\User\Infrastructure\Http\Concerns\FlashesDomainErrors;
use Src\Iam\User\Infrastructure\Http\Resources\EmailVerificationResource;

final class VerifyEmailController
{
    use FlashesDomainErrors;

    public function __invoke(
        Request $request,
        string $userId,
        string $token,
        VerifyEmailUseCase $verifyEmail,
    ): JsonResponse|RedirectResponse {
        try {
            $response = $verifyEmail->execute(new VerifyEmailCommand(
                userId: $userId,
                token: $token,
            ));
        } catch (InvalidVerificationTokenException|UserNotFoundException|UserAlreadyVerifiedException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            return $this->flashError(
                $exception->getMessage(),
                Auth::check() ? null : 'login',
            );
        }

        if ($request->expectsJson()) {
            return EmailVerificationResource::make($response)->response();
        }

        return Auth::check()
            ? redirect('/')->with('status', 'Email verified successfully.')
            : redirect()->route('login')->with('status', 'Email verified successfully.');
    }
}
