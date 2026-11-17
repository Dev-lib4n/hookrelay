<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Endpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Endpoint>
 */
class EndpointFactory extends Factory
{
    protected $model = Endpoint::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Webhook',
            'url' => 'https://example.com/webhooks/receive',
            'secret' => 'whsec_'.bin2hex(random_bytes(24)),
            'is_active' => true,
            'max_retries' => 5,
            'timeout_seconds' => 10,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
