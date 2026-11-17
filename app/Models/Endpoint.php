<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EndpointFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $url
 * @property string $secret
 * @property bool $is_active
 * @property int $max_retries
 * @property int $timeout_seconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Endpoint extends Model
{
    /** @use HasFactory<EndpointFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'url',
        'secret',
        'is_active',
        'max_retries',
        'timeout_seconds',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'max_retries' => 5,
        'timeout_seconds' => 10,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'is_active' => 'boolean',
            'max_retries' => 'integer',
            'timeout_seconds' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Endpoint $endpoint): void {
            if (empty($endpoint->getAttributes()['secret'])) {
                $endpoint->secret = 'whsec_'.bin2hex(random_bytes(24));
            }
        });
    }

    /**
     * @return HasMany<WebhookEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(WebhookEvent::class, 'endpoint_id');
    }
}
