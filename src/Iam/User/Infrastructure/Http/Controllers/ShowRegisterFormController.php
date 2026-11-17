<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Contracts\View\View;

final class ShowRegisterFormController
{
    public function __invoke(): View
    {
        /** @var view-string $view */
        $view = 'iam.auth.register';

        return view($view);
    }
}
