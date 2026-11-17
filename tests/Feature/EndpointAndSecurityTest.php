<?php

declare(strict_types=1);

use App\Enums\WebhookEventStatus;
use App\Jobs\DispatchWebhookJob;
use App\Models\Endpoint;
use App\Models\WebhookAttempt;
use App\Models\WebhookEvent;
use App\Rules\SafeExternalUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;

it('exposes the endpoint secret only on creation (201 Created) and encrypts it at rest', function (): void {
    $createResponse = $this->postJson('/api/v1/endpoints', [
        'name' => 'Partner ERP Webhook',
        'url' => 'https://partner.example.com/webhooks',
        'max_retries' => 5,
        'timeout_seconds' => 15,
    ]);

    $createResponse->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'url',
                'secret',
                'is_active',
                'max_retries',
                'timeout_seconds',
            ],
        ]);

    $endpointId = $createResponse->json('data.id');
    $plainSecret = $createResponse->json('data.secret');

    expect($plainSecret)->toStartWith('whsec_');

    // Verify encrypted at rest in PostgreSQL
    $rawRow = DB::table('endpoints')->where('id', $endpointId)->first();
    expect($rawRow)->not->toBeNull()
        ->and($rawRow->secret)->not->toBe($plainSecret);

    // Verify secret is omitted on GET show and index
    $showResponse = $this->getJson("/api/v1/endpoints/{$endpointId}");
    $showResponse->assertOk()
        ->assertJsonMissingPath('data.secret');

    $indexResponse = $this->getJson('/api/v1/endpoints');
    $indexResponse->assertOk()
        ->assertJsonMissingPath('data.0.secret');
});

it('manually retries a failed webhook event by resetting status and attempts_count while preserving attempt history', function (): void {
    Queue::fake();

    $endpoint = Endpoint::factory()->create();
    $event = WebhookEvent::factory()->create([
        'endpoint_id' => $endpoint->id,
        'status' => WebhookEventStatus::Failed,
        'attempts_count' => 5,
    ]);

    WebhookAttempt::factory()->create([
        'webhook_event_id' => $event->id,
        'attempt_number' => 1,
        'response_status' => 500,
    ]);

    $response = $this->postJson("/api/v1/events/{$event->id}/retry");

    $response->assertAccepted()
        ->assertJsonPath('data.status', WebhookEventStatus::Pending->value)
        ->assertJsonPath('data.attempts_count', 0);

    expect($event->attempts()->count())->toBe(1);

    Queue::assertPushedOn('webhooks', DispatchWebhookJob::class, function (DispatchWebhookJob $job) use ($event): bool {
        return $job->webhookEventId === $event->id;
    });
});

it('blocks SSRF attempts targeting localhost, loopback, private, and cloud metadata IPs', function (string $unsafeUrl): void {
    $validator = Validator::make(
        ['url' => $unsafeUrl],
        ['url' => [new SafeExternalUrl(enforceInTesting: true)]]
    );

    expect($validator->fails())->toBeTrue();
})->with([
    'localhost' => ['http://localhost/webhook'],
    'ipv4 loopback' => ['http://127.0.0.1:8080/internal'],
    'private class A' => ['http://10.0.0.15/admin'],
    'private class B' => ['http://172.16.0.10/secret'],
    'private class C' => ['http://192.168.1.1/router'],
    'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
    'non-http scheme' => ['ftp://8.8.8.8/file'],
]);

it('allows public external IPs in SafeExternalUrl validation', function (): void {
    $validator = Validator::make(
        ['url' => 'https://1.1.1.1/webhooks/receiver'],
        ['url' => [new SafeExternalUrl(enforceInTesting: true)]]
    );

    expect($validator->passes())->toBeTrue();
});

it('prunes webhook events and cascades attempts older than the retention policy via webhooks:prune', function (): void {
    $endpoint = Endpoint::factory()->create();

    $oldEvent = WebhookEvent::factory()->create([
        'endpoint_id' => $endpoint->id,
        'created_at' => now()->subDays(35),
    ]);

    WebhookAttempt::factory()->create([
        'webhook_event_id' => $oldEvent->id,
    ]);

    $recentEvent = WebhookEvent::factory()->create([
        'endpoint_id' => $endpoint->id,
        'created_at' => now()->subDays(5),
    ]);

    $this->artisan('webhooks:prune --days=30')
        ->assertSuccessful();

    $this->assertDatabaseMissing('webhook_events', ['id' => $oldEvent->id]);
    $this->assertDatabaseMissing('webhook_attempts', ['webhook_event_id' => $oldEvent->id]);
    $this->assertDatabaseHas('webhook_events', ['id' => $recentEvent->id]);
});
