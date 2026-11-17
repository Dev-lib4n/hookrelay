<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $endpoint_id
 * @property string|null $idempotency_key
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property array<string, string>|null $headers
 * @property WebhookEventStatus $status
 * @property int $attempts_count
 * @property Carbon|null $delivered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Endpoint $endpoint
 */
class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'endpoint_id',
        'idempotency_key',
        'event_type',
        'payload',
        'headers',
        'status',
        'attempts_count',
        'delivered_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'attempts_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'status' => WebhookEventStatus::class,
            'attempts_count' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Endpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class, 'endpoint_id');
    }

    /**
     * @return HasMany<WebhookAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(WebhookAttempt::class, 'webhook_event_id')
            ->orderBy('attempt_number');
    }
}
