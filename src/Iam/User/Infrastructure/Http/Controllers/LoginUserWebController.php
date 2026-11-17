<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Src\Iam\User\Application\DTOs\LoginUserCommand;
use Src\Iam\User\Application\UseCases\LoginUserUseCase;
use Src\Iam\User\Domain\Exceptions\InvalidCredentialsException;
use Src\Iam\User\Infrastructure\Http\Concerns\FlashesDomainErrors;
use Src\Iam\User\Infrastructure\Http\Requests\LoginUserWebRequest;
use Src\Iam\User\Infrastructure\Models\UserModel;

final class LoginUserWebController
{
    use FlashesDomainErrors;

    public function __invoke(
        LoginUserWebRequest $request,
        LoginUserUseCase $loginUser,
    ): RedirectResponse {
        try {
            $response = $loginUser->execute(new LoginUserCommand(
                email: $request->string('email')->toString(),
                password: $request->string('password')->toString(),
            ));
        } catch (InvalidCredentialsException $exception) {
            return $this->flashError($exception->getMessage());
        }

        $user = UserModel::query()->findOrFail($response->id);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(
            $user->verified_at === null ? route('verification.notice') : '/'
        )->with('status', 'Welcome back.');
    }
}
