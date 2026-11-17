<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEndpointRequest;
use App\Http\Requests\Api\V1\UpdateEndpointRequest;
use App\Http\Resources\EndpointResource;
use App\Models\Endpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class EndpointController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $endpoints = Endpoint::query()
            ->latest()
            ->paginate(15);

        return EndpointResource::collection($endpoints);
    }

    public function store(StoreEndpointRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $endpoint = Endpoint::query()->create($validated);

        return (new EndpointResource($endpoint))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Endpoint $endpoint): EndpointResource
    {
        return new EndpointResource($endpoint);
    }

    public function update(UpdateEndpointRequest $request, Endpoint $endpoint): EndpointResource
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $endpoint->update($validated);

        return new EndpointResource($endpoint->refresh());
    }

    public function destroy(Endpoint $endpoint): JsonResponse
    {
        $endpoint->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
