<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webhook_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'resource_type',
        'resource_id',
        'signature_verified',
        'status',
        'payload_json',
        'error_message',
        'processed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'status'             => WebhookEventStatus::class,
            'payload_json'       => 'array',
            'processed_at'       => 'datetime',
        ];
    }

    /**
     * Scope to locate by gateway event ID.
     */
    public function scopeByEventId($query, string $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Whether the event was successfully processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === WebhookEventStatus::PROCESSED;
    }
}
