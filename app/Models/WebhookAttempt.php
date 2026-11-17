<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WebhookAttemptFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $webhook_event_id
 * @property int $attempt_number
 * @property int|null $response_status
 * @property string|null $response_body
 * @property int|null $duration_ms
 * @property string|null $error_message
 * @property Carbon $created_at
 * @property-read WebhookEvent $webhookEvent
 */
class WebhookAttempt extends Model
{
    /** @use HasFactory<WebhookAttemptFactory> */
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'webhook_event_id',
        'attempt_number',
        'response_status',
        'response_body',
        'duration_ms',
        'error_message',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'response_status' => 'integer',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WebhookEvent, $this>
     */
    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'webhook_event_id');
    }
}
