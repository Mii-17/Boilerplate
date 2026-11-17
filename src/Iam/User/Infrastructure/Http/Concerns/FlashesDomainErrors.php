<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Concerns;

use Illuminate\Http\RedirectResponse;

trait FlashesDomainErrors
{
    protected function flashError(string $message, ?string $route = null): RedirectResponse
    {
        return $route === null
            ? redirect()->back()->with('error', $message)
            : redirect()->route($route)->with('error', $message);
    }
}
