<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\IngestWebhookData;
use Illuminate\Foundation\Http\FormRequest;

class IngestWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', 'max:150'],
            'payload' => ['required', 'array'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['string', 'max:2048'],
        ];
    }

    public function toDto(): IngestWebhookData
    {
        /** @var string $eventType */
        $eventType = $this->validated('event_type');

        /** @var array<string, mixed> $payload */
        $payload = $this->validated('payload');

        /** @var array<string, string>|null $headers */
        $headers = $this->validated('headers');

        $idempotencyHeader = $this->header('X-Idempotency-Key');
        $idempotencyKey = is_string($idempotencyHeader) && trim($idempotencyHeader) !== ''
            ? trim($idempotencyHeader)
            : null;

        return new IngestWebhookData(
            eventType: $eventType,
            payload: $payload,
            headers: $headers,
            idempotencyKey: $idempotencyKey,
        );
    }
}
