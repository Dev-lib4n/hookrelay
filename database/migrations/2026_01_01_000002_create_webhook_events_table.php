<?php

declare(strict_types=1);

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('endpoint_id')
                ->constrained('endpoints')
                ->cascadeOnDelete();
            $table->string('idempotency_key')->nullable();
            $table->string('event_type')->index();
            $table->jsonb('payload');
            $table->jsonb('headers')->nullable();
            $table->string('status')->default(WebhookEventStatus::Pending->value)->index();
            $table->integer('attempts_count')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['endpoint_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
