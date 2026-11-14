<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeUnverifiedEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
