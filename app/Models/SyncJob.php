<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model representing a synchronization job/session for a connector.
 */
class SyncJob extends Model
{
    protected $fillable = [
        'connector',
        'query',
        'started_at',
        'finished_at',
        'total_found',
        'downloaded',
        'skipped',
        'failed',
        'status',
        'duration_ms',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'total_found' => 'integer',
        'downloaded'  => 'integer',
        'skipped'     => 'integer',
        'failed'      => 'integer',
        'duration_ms' => 'integer',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(InboxMessage::class);
    }
}
