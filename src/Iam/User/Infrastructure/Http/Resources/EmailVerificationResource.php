<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Iam\User\Application\DTOs\VerifyEmailResponse;

/** @property VerifyEmailResponse $resource */
final class EmailVerificationResource extends JsonResource
{
    /**
     * @return array<string, bool|string>
     */
    public function toArray(Request $request): array
    {
        /** @var VerifyEmailResponse $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'email' => $resource->email,
            'isVerified' => $resource->isVerified,
        ];
    }
}
