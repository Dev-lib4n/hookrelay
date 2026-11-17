<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Endpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Endpoint
 */
class EndpointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Endpoint $endpoint */
        $endpoint = $this->resource;

        return [
            'id' => $endpoint->id,
            'name' => $endpoint->name,
            'url' => $endpoint->url,
            'secret' => $this->when(
                $endpoint->wasRecentlyCreated,
                fn (): string => $endpoint->secret
            ),
            'is_active' => $endpoint->is_active,
            'max_retries' => $endpoint->max_retries,
            'timeout_seconds' => $endpoint->timeout_seconds,
            'created_at' => $endpoint->created_at?->toIso8601String(),
            'updated_at' => $endpoint->updated_at?->toIso8601String(),
        ];
    }
}
