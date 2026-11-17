<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\EndpointController;
use App\Http\Controllers\Api\V1\IngestWebhookController;
use App\Http\Controllers\Api\V1\RetryWebhookEventController;
use App\Http\Controllers\Api\V1\WebhookEventController;
use App\Http\Middleware\VerifyApiKey;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['throttle:60,1', VerifyApiKey::class])
    ->group(function (): void {
        Route::post('/ingest/{endpoint}', IngestWebhookController::class)
            ->whereUuid('endpoint')
            ->name('webhooks.ingest');

        Route::apiResource('endpoints', EndpointController::class)
            ->whereUuid('endpoint');

        Route::get('/events', [WebhookEventController::class, 'index'])
            ->name('events.index');

        Route::get('/events/{event}', [WebhookEventController::class, 'show'])
            ->whereUuid('event')
            ->name('events.show');

        Route::post('/events/{event}/retry', RetryWebhookEventController::class)
            ->whereUuid('event')
            ->name('events.retry');
    });
