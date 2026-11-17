<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class IngestWebhookData
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>|null  $headers
     */
    public function __construct(
        public string $eventType,
        public array $payload,
        public ?array $headers = null,
        public ?string $idempotencyKey = null,
    ) {}
}
