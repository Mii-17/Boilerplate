<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Src\Iam\User\Application\DTOs\ChangeUnverifiedEmailCommand;
use Src\Iam\User\Application\UseCases\ChangeUnverifiedEmailUseCase;
use Src\Iam\User\Domain\Exceptions\UserAlreadyVerifiedException;
use Src\Iam\User\Domain\Exceptions\UserEmailAlreadyTakenException;
use Src\Iam\User\Domain\Exceptions\UserNotFoundException;
use Src\Iam\User\Infrastructure\Http\Concerns\FlashesDomainErrors;
use Src\Iam\User\Infrastructure\Http\Requests\ChangeUnverifiedEmailWebRequest;
use Src\Iam\User\Infrastructure\Models\UserModel;

final class ChangeUnverifiedEmailWebController
{
    use FlashesDomainErrors;

    public function __invoke(
        ChangeUnverifiedEmailUseCase $changeEmail,
        ChangeUnverifiedEmailWebRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof UserModel);

        try {
            $changeEmail->execute(new ChangeUnverifiedEmailCommand(
                id: $user->id,
                newEmail: $request->string('email')->toString(),
            ));
        } catch (UserEmailAlreadyTakenException|UserNotFoundException|UserAlreadyVerifiedException $exception) {
            return $this->flashError($exception->getMessage());
        }

        return redirect()->route('verification.notice')
            ->with('status', 'Email updated. Please verify your new address.');
    }
}
