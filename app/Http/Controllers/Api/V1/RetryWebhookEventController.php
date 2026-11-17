<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\RetryWebhookEventAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\WebhookEventResource;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RetryWebhookEventController extends Controller
{
    public function __invoke(
        WebhookEvent $event,
        RetryWebhookEventAction $action,
    ): JsonResponse {
        $retriedEvent = $action->execute($event);

        return response()->json([
            'message' => 'Webhook event queued for manual retry.',
            'event_id' => $retriedEvent->id,
            'data' => new WebhookEventResource($retriedEvent),
        ], Response::HTTP_ACCEPTED);
    }
}
