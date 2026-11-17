<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoints', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('url');
            $table->text('secret');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('max_retries')->default(5);
            $table->integer('timeout_seconds')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoints');
    }
};
