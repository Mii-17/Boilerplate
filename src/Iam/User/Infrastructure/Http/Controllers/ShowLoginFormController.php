<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Controllers;

use Illuminate\Contracts\View\View;

final class ShowLoginFormController
{
    public function __invoke(): View
    {
        /** @var view-string $view */
        $view = 'iam.auth.login';

        return view($view);
    }
}
