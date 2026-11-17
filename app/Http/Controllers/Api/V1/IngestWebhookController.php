<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\IngestWebhookAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IngestWebhookRequest;
use App\Http\Resources\WebhookEventResource;
use App\Models\Endpoint;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class IngestWebhookController extends Controller
{
    public function __invoke(
        IngestWebhookRequest $request,
        Endpoint $endpoint,
        IngestWebhookAction $action,
    ): JsonResponse {
        $result = $action->execute($endpoint, $request->toDto());

        $statusCode = $result->wasDuplicate
            ? Response::HTTP_OK
            : Response::HTTP_ACCEPTED;

        return response()->json([
            'message' => $result->wasDuplicate
                ? 'Webhook event already ingested (idempotent replay).'
                : 'Webhook event accepted and queued for delivery.',
            'event_id' => $result->event->id,
            'idempotent_replay' => $result->wasDuplicate,
            'data' => new WebhookEventResource($result->event),
        ], $statusCode);
    }
}
