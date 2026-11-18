<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Iam\User\Application\DTOs\ChangeUnverifiedEmailResponse;

/** @property ChangeUnverifiedEmailResponse $resource */
final class EmailChangeResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        /** @var ChangeUnverifiedEmailResponse $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'email' => $resource->email,
        ];
    }
}
