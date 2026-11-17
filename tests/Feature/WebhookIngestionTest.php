<?php

declare(strict_types=1);

use App\Enums\WebhookEventStatus;
use App\Jobs\DispatchWebhookJob;
use App\Models\Endpoint;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Queue;

it('ingests a valid webhook event, stores it as pending, and dispatches the job to the webhooks queue', function (): void {
    Queue::fake();

    $endpoint = Endpoint::factory()->create();

    $response = $this->postJson("/api/v1/ingest/{$endpoint->id}", [
        'event_type' => 'order.paid',
        'payload' => [
            'order_id' => 'ord_98765',
            'amount' => 19990,
            'currency' => 'BRL',
        ],
        'headers' => [
            'X-Custom-Source' => 'checkout-v2',
        ],
    ]);

    $response->assertAccepted()
        ->assertJsonPath('idempotent_replay', false)
        ->assertJsonPath('data.event_type', 'order.paid')
        ->assertJsonPath('data.status', WebhookEventStatus::Pending->value);

    $eventId = $response->json('event_id');

    $this->assertDatabaseHas('webhook_events', [
        'id' => $eventId,
        'endpoint_id' => $endpoint->id,
        'event_type' => 'order.paid',
        'status' => WebhookEventStatus::Pending->value,
        'attempts_count' => 0,
    ]);

    Queue::assertPushedOn('webhooks', DispatchWebhookJob::class, function (DispatchWebhookJob $job) use ($eventId): bool {
        return $job->webhookEventId === $eventId;
    });
});

it('blocks duplicate ingestion using X-Idempotency-Key and returns 200 OK without re-queueing', function (): void {
    Queue::fake();

    $endpoint = Endpoint::factory()->create();
    $idempotencyKey = 'idem_key_order_12345';

    $payload = [
        'event_type' => 'order.paid',
        'payload' => ['order_id' => 'ord_12345'],
    ];

    $firstResponse = $this->withHeaders([
        'X-Idempotency-Key' => $idempotencyKey,
    ])->postJson("/api/v1/ingest/{$endpoint->id}", $payload);

    $firstResponse->assertAccepted()
        ->assertJsonPath('idempotent_replay', false);

    $secondResponse = $this->withHeaders([
        'X-Idempotency-Key' => $idempotencyKey,
    ])->postJson("/api/v1/ingest/{$endpoint->id}", $payload);

    $secondResponse->assertOk()
        ->assertJsonPath('idempotent_replay', true)
        ->assertJsonPath('event_id', $firstResponse->json('event_id'));

    expect(WebhookEvent::query()->count())->toBe(1);
    Queue::assertPushed(DispatchWebhookJob::class, 1);
});

it('redacts sensitive headers before persisting to PostgreSQL (LGPD compliance)', function (): void {
    Queue::fake();

    $endpoint = Endpoint::factory()->create();

    $response = $this->postJson("/api/v1/ingest/{$endpoint->id}", [
        'event_type' => 'user.created',
        'payload' => ['user_id' => 'usr_1'],
        'headers' => [
            'Authorization' => 'Bearer secret-jwt-token',
            'Cookie' => 'session_id=xyz987',
            'X-Api-Key' => 'internal-secret-key',
            'X-Correlation-Id' => 'corr-12345',
        ],
    ]);

    $response->assertAccepted();

    $event = WebhookEvent::query()->findOrFail($response->json('event_id'));

    expect($event->headers)->toEqual([
        'Authorization' => '[REDACTED]',
        'Cookie' => '[REDACTED]',
        'X-Api-Key' => '[REDACTED]',
        'X-Correlation-Id' => 'corr-12345',
    ]);
});

it('rejects ingestion when the target endpoint is inactive', function (): void {
    Queue::fake();

    $endpoint = Endpoint::factory()->inactive()->create();

    $response = $this->postJson("/api/v1/ingest/{$endpoint->id}", [
        'event_type' => 'order.paid',
        'payload' => ['order_id' => 'ord_inactive'],
    ]);

    $response->assertUnprocessable();
    Queue::assertNothingPushed();
});
