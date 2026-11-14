<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LoginResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        /** @var string $token */
        $token = $this->resource;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
