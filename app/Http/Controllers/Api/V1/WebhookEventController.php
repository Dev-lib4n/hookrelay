<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WebhookEventResource;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebhookEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = WebhookEvent::query()->latest();

        if ($request->filled('endpoint_id') && is_string($request->query('endpoint_id'))) {
            $query->where('endpoint_id', $request->query('endpoint_id'));
        }

        if ($request->filled('status') && is_string($request->query('status'))) {
            $query->where('status', $request->query('status'));
        }

        return WebhookEventResource::collection($query->paginate(15));
    }

    public function show(WebhookEvent $event): WebhookEventResource
    {
        $event->load('attempts');

        return new WebhookEventResource($event);
    }
}
