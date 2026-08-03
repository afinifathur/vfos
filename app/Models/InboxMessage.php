<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model representing a generalized message cached locally via a Connector.
 */
class InboxMessage extends Model
{
    // Lifecycle status constants
    public const STATUS_NEW      = 'NEW';
    public const STATUS_SYNCED   = 'SYNCED';
    public const STATUS_PARSED   = 'PARSED';
    public const STATUS_READY    = 'READY';
    public const STATUS_IMPORTED = 'IMPORTED';
    public const STATUS_FAILED   = 'FAILED';
    public const STATUS_IGNORED  = 'IGNORED';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    protected $fillable = [
        'user_id',
        'sync_job_id',
        'transaction_id',
        'connector',
        'provider',
        'google_message_id',
        'google_thread_id',
        'history_id',
        'internal_date',
        'subject',
        'from',
        'to',
        'snippet',
        'html_body',
        'plain_body',
        'raw_payload',
        'labels',
        'checksum',
        'status',
        'parsed_at',
        'imported_at',
    ];

    protected $casts = [
        'raw_payload'   => 'array',
        'labels'        => 'array',
        'parsed_at'     => 'datetime',
        'imported_at'   => 'datetime',
        'internal_date' => 'integer',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(SyncJob::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Search scope to filter messages by keyword.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
              ->orWhere('from', 'like', "%{$search}%")
              ->orWhere('to', 'like', "%{$search}%")
              ->orWhere('snippet', 'like', "%{$search}%")
              ->orWhere('google_message_id', 'like', "%{$search}%");
        });
    }

    /**
     * Normalizes a payload array by recursively sorting its keys alphabetically.
     * Ensures consistent JSON string outputs for hashing.
     */
    public static function normalizePayload(array $payload): array
    {
        self::ksortRecursive($payload);
        return $payload;
    }

    private static function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::ksortRecursive($value);
            }
        }
    }
}
