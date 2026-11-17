<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Src\Iam\User\Application\DTOs\RegisterUserCommand;
use Src\Iam\User\Application\UseCases\RegisterUserUseCase;
use Src\Iam\User\Domain\Exceptions\UserAlreadyExistsException;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidPlainPasswordException;
use Src\Iam\User\Infrastructure\Http\Concerns\FlashesDomainErrors;
use Src\Iam\User\Infrastructure\Http\Requests\RegisterUserWebRequest;
use Src\Iam\User\Infrastructure\Models\UserModel;

final class RegisterUserWebController
{
    use FlashesDomainErrors;

    public function __invoke(
        RegisterUserWebRequest $request,
        RegisterUserUseCase $registerUser,
    ): RedirectResponse {
        try {
            $response = $registerUser->execute(new RegisterUserCommand(
                name: $request->string('name')->toString(),
                email: $request->string('email')->toString(),
                plainPassword: $request->string('password')->toString(),
            ));
        } catch (InvalidPlainPasswordException $exception) {
            return redirect()->back()
                ->withErrors(['password' => $exception->getMessage()])
                ->withInput($request->except(['password', 'password_confirmation']));
        } catch (UserAlreadyExistsException $exception) {
            return $this->flashError($exception->getMessage());
        }

        Auth::login(UserModel::query()->findOrFail($response->id));

        return redirect()->route('verification.notice')
            ->with('status', 'Registration successful. Please verify your email.');
    }
}
