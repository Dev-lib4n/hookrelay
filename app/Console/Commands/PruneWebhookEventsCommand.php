<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookEvent;
use Illuminate\Console\Command;

class PruneWebhookEventsCommand extends Command
{
    protected $signature = 'webhooks:prune {--days= : Number of retention days to keep webhook events}';

    protected $description = 'Prune webhook events and their cascaded delivery attempts older than the retention window (LGPD compliance).';

    public function handle(): int
    {
        $daysOption = $this->option('days');

        /** @var int $configuredDays */
        $configuredDays = config('webhooks.retention_days', 30);

        $retentionDays = is_numeric($daysOption)
            ? max(1, (int) $daysOption)
            : max(1, (int) $configuredDays);

        $cutoff = now()->subDays($retentionDays);

        $deletedCount = WebhookEvent::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deletedCount} webhook event(s) older than {$retentionDays} days (before {$cutoff->toIso8601String()}).");

        return self::SUCCESS;
    }
}
