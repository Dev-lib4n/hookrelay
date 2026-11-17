<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WebhookEventStatus;
use App\Models\Endpoint;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'endpoint_id' => Endpoint::factory(),
            'idempotency_key' => $this->faker->uuid(),
            'event_type' => $this->faker->randomElement(['order.paid', 'user.created', 'invoice.failed']),
            'payload' => [
                'id' => $this->faker->uuid(),
                'amount' => $this->faker->numberBetween(1000, 50000),
                'currency' => 'BRL',
            ],
            'headers' => [
                'X-Source-System' => 'billing-service',
            ],
            'status' => WebhookEventStatus::Pending,
            'attempts_count' => 0,
            'delivered_at' => null,
        ];
    }
}
