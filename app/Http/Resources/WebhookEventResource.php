<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WebhookEvent
 */
class WebhookEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WebhookEvent $event */
        $event = $this->resource;

        return [
            'id' => $event->id,
            'endpoint_id' => $event->endpoint_id,
            'idempotency_key' => $event->idempotency_key,
            'event_type' => $event->event_type,
            'payload' => $event->payload,
            'headers' => $event->headers,
            'status' => $event->status->value,
            'attempts_count' => $event->attempts_count,
            'delivered_at' => $event->delivered_at?->toIso8601String(),
            'created_at' => $event->created_at?->toIso8601String(),
            'updated_at' => $event->updated_at?->toIso8601String(),
            'attempts' => WebhookAttemptResource::collection($this->whenLoaded('attempts')),
        ];
    }
}
