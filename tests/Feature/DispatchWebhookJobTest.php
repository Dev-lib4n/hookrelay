<?php

declare(strict_types=1);

use App\Enums\WebhookEventStatus;
use App\Jobs\DispatchWebhookJob;
use App\Models\Endpoint;
use App\Models\WebhookEvent;
use App\Services\WebhookSignatureService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('dispatches webhook with valid replay-resistant HMAC-SHA256 signature and marks event as delivered on 2xx', function (): void {
    Http::fake([
        'https://example.com/*' => Http::response(['received' => true], 200),
    ]);

    $endpoint = Endpoint::factory()->create([
        'url' => 'https://example.com/webhooks/orders',
        'secret' => 'whsec_test_secret_key_123456',
    ]);

    $event = WebhookEvent::factory()->create([
        'endpoint_id' => $endpoint->id,
        'event_type' => 'order.paid',
        'payload' => [
            'order_id' => 'ord_555',
            'total' => 25000,
        ],
    ]);

    $job = new DispatchWebhookJob($event->id);
    $job->handle(app(WebhookSignatureService::class));

    Http::assertSent(function (Request $request) use ($endpoint, $event): bool {
        $rawBody = $request->body();
        $timestampHeader = $request->header('X-Webhook-Timestamp')[0] ?? '';
        $signatureHeader = $request->header('X-Webhook-Signature')[0] ?? '';
        $expectedSignature = hash_hmac('sha256', "{$timestampHeader}.{$rawBody}", $endpoint->secret);

        return $request->url() === $endpoint->url
            && json_decode($rawBody, true) == $event->payload
            && $request->hasHeader('X-Webhook-Id', $event->id)
            && $request->hasHeader('X-Webhook-Event', 'order.paid')
            && $timestampHeader !== ''
            && hash_equals($expectedSignature, $signatureHeader);
    });

    $event->refresh();

    expect($event->status)->toBe(WebhookEventStatus::Delivered)
        ->and($event->attempts_count)->toBe(1)
        ->and($event->delivered_at)->not->toBeNull();

    $this->assertDatabaseHas('webhook_attempts', [
        'webhook_event_id' => $event->id,
        'attempt_number' => 1,
        'response_status' => 200,
        'error_message' => null,
    ]);
});

it('records failed 500 attempts and transitions event to failed after exhausting max_retries', function (): void {
    Http::fake([
        'https://example.com/*' => Http::response('Internal Server Error', 500),
    ]);

    $endpoint = Endpoint::factory()->create([
        'url' => 'https://example.com/webhooks/failing',
        'max_retries' => 3,
    ]);

    $event = WebhookEvent::factory()->create([
        'endpoint_id' => $endpoint->id,
        'event_type' => 'invoice.failed',
    ]);

    $signatureService = app(WebhookSignatureService::class);

    // Attempt 1 -> Pending (released for retry)
    (new DispatchWebhookJob($event->id))->handle($signatureService);
    $event->refresh();
    expect($event->status)->toBe(WebhookEventStatus::Pending)
        ->and($event->attempts_count)->toBe(1);

    // Attempt 2 -> Pending (released for retry)
    (new DispatchWebhookJob($event->id))->handle($signatureService);
    $event->refresh();
    expect($event->status)->toBe(WebhookEventStatus::Pending)
        ->and($event->attempts_count)->toBe(2);

    // Attempt 3 -> Exhausted max_retries (3), transitions to Failed
    $finalJob = new DispatchWebhookJob($event->id);
    $finalJob->handle($signatureService);
    $event->refresh();

    expect($event->status)->toBe(WebhookEventStatus::Failed)
        ->and($event->attempts_count)->toBe(3)
        ->and($event->delivered_at)->toBeNull()
        ->and($event->attempts()->count())->toBe(3)
        ->and($finalJob->backoff())->toBe([10, 60, 300, 900, 3600]);

    $this->assertDatabaseHas('webhook_attempts', [
        'webhook_event_id' => $event->id,
        'attempt_number' => 3,
        'response_status' => 500,
        'response_body' => 'Internal Server Error',
        'error_message' => 'Destination returned HTTP 500.',
    ]);
});
