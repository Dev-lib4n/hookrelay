<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\WebhookEvent;

final readonly class IngestWebhookResult
{
    public function __construct(
        public WebhookEvent $event,
        public bool $wasDuplicate,
    ) {}
}
