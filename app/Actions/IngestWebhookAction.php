<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\IngestWebhookData;
use App\DTOs\IngestWebhookResult;
use App\Enums\WebhookEventStatus;
use App\Jobs\DispatchWebhookJob;
use App\Models\Endpoint;
use App\Models\WebhookEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class IngestWebhookAction
{
    public function execute(Endpoint $endpoint, IngestWebhookData $data): IngestWebhookResult
    {
        if (! $endpoint->is_active) {
            throw new UnprocessableEntityHttpException('The target endpoint is currently inactive.');
        }

        if ($data->idempotencyKey !== null && $data->idempotencyKey !== '') {
            $existingEvent = WebhookEvent::query()
                ->where('endpoint_id', $endpoint->id)
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existingEvent !== null) {
                return new IngestWebhookResult(
                    event: $existingEvent,
                    wasDuplicate: true,
                );
            }
        }

        $sanitizedHeaders = $this->sanitizeHeaders($data->headers);

        $event = WebhookEvent::query()->create([
            'endpoint_id' => $endpoint->id,
            'idempotency_key' => ($data->idempotencyKey !== null && $data->idempotencyKey !== '')
                ? $data->idempotencyKey
                : null,
            'event_type' => $data->eventType,
            'payload' => $data->payload,
            'headers' => $sanitizedHeaders,
            'status' => WebhookEventStatus::Pending,
            'attempts_count' => 0,
        ]);

        DispatchWebhookJob::dispatch($event->id)->onQueue('webhooks');

        return new IngestWebhookResult(
            event: $event,
            wasDuplicate: false,
        );
    }

    /**
     * Redact sensitive headers for LGPD / Privacy by Design compliance before persistence.
     *
     * @param  array<string, string>|null  $headers
     * @return array<string, string>|null
     */
    private function sanitizeHeaders(?array $headers): ?array
    {
        if ($headers === null || $headers === []) {
            return $headers;
        }

        /** @var list<string> $sensitiveKeys */
        $sensitiveKeys = config('webhooks.sensitive_headers', [
            'authorization',
            'cookie',
            'set-cookie',
            'x-api-key',
            'proxy-authorization',
        ]);

        $normalizedSensitive = array_map('strtolower', $sensitiveKeys);

        $sanitized = [];
        foreach ($headers as $key => $value) {
            if (in_array(strtolower((string) $key), $normalizedSensitive, true)) {
                $sanitized[(string) $key] = '[REDACTED]';
            } else {
                $sanitized[(string) $key] = (string) $value;
            }
        }

        return $sanitized;
    }
}
