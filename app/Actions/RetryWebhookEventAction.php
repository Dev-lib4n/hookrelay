<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\WebhookEventStatus;
use App\Jobs\DispatchWebhookJob;
use App\Models\WebhookEvent;

class RetryWebhookEventAction
{
    public function execute(WebhookEvent $event): WebhookEvent
    {
        $event->update([
            'status' => WebhookEventStatus::Pending,
            'attempts_count' => 0,
            'delivered_at' => null,
        ]);

        DispatchWebhookJob::dispatch($event->id)->onQueue('webhooks');

        return $event->refresh();
    }
}
