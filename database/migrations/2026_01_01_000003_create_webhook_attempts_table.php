<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('webhook_event_id')
                ->constrained('webhook_events')
                ->cascadeOnDelete();
            $table->integer('attempt_number');
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['webhook_event_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_attempts');
    }
};
