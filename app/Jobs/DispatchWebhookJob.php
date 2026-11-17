<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookAttempt;
use App\Models\WebhookEvent;
use App\Services\WebhookSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Worker ceiling; actual retry limit is governed dynamically by $endpoint->max_retries.
     */
    public int $tries = 25;

    public function __construct(
        public readonly string $webhookEventId,
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Exponential backoff schedule in seconds.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        /** @var list<int> $backoff */
        $backoff = config('webhooks.backoff', [10, 60, 300, 900, 3600]);

        return $backoff;
    }

    public function handle(WebhookSignatureService $signatureService): void
    {
        $event = WebhookEvent::query()
            ->with('endpoint')
            ->find($this->webhookEventId);

        if ($event === null || $event->status === WebhookEventStatus::Delivered) {
            return;
        }

        $endpoint = $event->endpoint;
        if (! $endpoint->is_active) {
            $event->update([
                'status' => WebhookEventStatus::Failed,
            ]);

            return;
        }

        $event->update([
            'status' => WebhookEventStatus::Processing,
        ]);

        $rawPayload = json_encode(
            $event->payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $timestamp = (string) now()->timestamp;
        $signature = $signatureService->sign($rawPayload, $endpoint->secret, $timestamp);

        $customHeaders = is_array($event->headers) ? $event->headers : [];
        $headers = array_merge($customHeaders, [
            'Content-Type' => 'application/json',
            'User-Agent' => 'HookRelay-Webhook-Broker/1.0',
            'X-Webhook-Id' => $event->id,
            'X-Webhook-Event' => $event->event_type,
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
        ]);

        $attemptNumber = $event->attempts()->count() + 1;
        $startTime = hrtime(true);

        $responseStatus = null;
        $responseBody = null;
        $errorMessage = null;
        $isSuccessful = false;

        try {
            $response = Http::timeout($endpoint->timeout_seconds)
                ->withHeaders($headers)
                ->withBody($rawPayload, 'application/json')
                ->post($endpoint->url);

            $responseStatus = $response->status();
            $responseBody = $this->truncateBody($response->body());
            $isSuccessful = $response->successful();

            if (! $isSuccessful) {
                $errorMessage = "Destination returned HTTP {$responseStatus}.";
            }
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }

        $durationMs = (int) round((hrtime(true) - $startTime) / 1_000_000);

        WebhookAttempt::query()->create([
            'webhook_event_id' => $event->id,
            'attempt_number' => $attemptNumber,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'duration_ms' => $durationMs,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);

        $newAttemptsCount = $event->attempts_count + 1;

        if ($isSuccessful) {
            $event->update([
                'status' => WebhookEventStatus::Delivered,
                'attempts_count' => $newAttemptsCount,
                'delivered_at' => now(),
            ]);

            return;
        }

        $maxRetries = max(1, $endpoint->max_retries);

        if ($newAttemptsCount >= $maxRetries) {
            $event->update([
                'status' => WebhookEventStatus::Failed,
                'attempts_count' => $newAttemptsCount,
            ]);

            return;
        }

        $event->update([
            'status' => WebhookEventStatus::Pending,
            'attempts_count' => $newAttemptsCount,
        ]);

        $this->release($this->resolveDelayForAttempt($newAttemptsCount));
    }

    private function resolveDelayForAttempt(int $attemptCount): int
    {
        $schedule = $this->backoff();
        $index = max(0, min($attemptCount - 1, count($schedule) - 1));

        return $schedule[$index] ?? 60;
    }

    private function truncateBody(string $body): string
    {
        /** @var int $maxLength */
        $maxLength = config('webhooks.max_response_body_length', 10000);

        if (mb_strlen($body) <= $maxLength) {
            return $body;
        }

        return mb_substr($body, 0, $maxLength).'... [TRUNCATED]';
    }
}
