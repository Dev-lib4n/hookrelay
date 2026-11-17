<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Endpoint;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Endpoint::factory()->create([
            'name' => 'Default Billing Receiver',
            'url' => 'https://example.com/webhooks/billing',
        ]);
    }
}
