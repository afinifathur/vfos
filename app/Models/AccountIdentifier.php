<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccountIdentifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'account_id',
        'type',
        'provider',
        'value',
        'original_value',
        'priority',
        'source',
        'archived_at',
    ];

    protected $casts = [
        'priority' => 'integer',
        'archived_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::creating(function ($identifier) {
            if (empty($identifier->uuid)) {
                $identifier->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationship to the related Account.
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Scope to only include active (non-archived) identifiers.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }
}
