<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Application-level settings store.
 *
 * This model is intentionally simple — it stores raw values (plain or encrypted).
 * Encryption/decryption is handled exclusively by AppSettingRepository
 * to keep the model free of business logic.
 *
 * @property int    $id
 * @property string $key
 * @property string|null $value
 * @property string $group
 * @property bool   $is_encrypted
 */
class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Find a setting by key.
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Check if a non-null value exists for the given key.
     */
    public static function hasKey(string $key): bool
    {
        return static::where('key', $key)->whereNotNull('value')->exists();
    }
}
