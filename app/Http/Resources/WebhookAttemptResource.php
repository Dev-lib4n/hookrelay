<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WebhookAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WebhookAttempt
 */
class WebhookAttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WebhookAttempt $attempt */
        $attempt = $this->resource;

        return [
            'id' => $attempt->id,
            'webhook_event_id' => $attempt->webhook_event_id,
            'attempt_number' => $attempt->attempt_number,
            'response_status' => $attempt->response_status,
            'response_body' => $attempt->response_body,
            'duration_ms' => $attempt->duration_ms,
            'error_message' => $attempt->error_message,
            'created_at' => $attempt->created_at->toIso8601String(),
        ];
    }
}
