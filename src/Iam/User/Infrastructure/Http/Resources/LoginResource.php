<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Iam\User\Application\DTOs\AuthTokenResponse;

/** @property AuthTokenResponse $resource */
final class LoginResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        /** @var AuthTokenResponse $resource */
        $resource = $this->resource;

        return [
            'token' => $resource->token,
            'token_type' => $resource->tokenType,
        ];
    }
}
