<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WebhookAttempt;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookAttempt>
 */
class WebhookAttemptFactory extends Factory
{
    protected $model = WebhookAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_event_id' => WebhookEvent::factory(),
            'attempt_number' => 1,
            'response_status' => 200,
            'response_body' => '{"ok":true}',
            'duration_ms' => 45,
            'error_message' => null,
            'created_at' => now(),
        ];
    }
}
