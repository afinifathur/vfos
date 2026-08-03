<?php

namespace App\Repositories;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Central access point for application-level settings.
 *
 * Encryption responsibility lives HERE, not in the Model.
 * The Model stores raw values (ciphertext for encrypted keys).
 *
 * Encrypted keys (NEVER returned in plaintext from the UI layer):
 *  - google_client_secret
 */
class AppSettingRepository
{
    /** Keys whose values must always be encrypted at rest. */
    private const ENCRYPTED_KEYS = [
        'google_client_secret',
    ];

    // ─── Read ────────────────────────────────────────────────────────────────

    /**
     * Retrieve a setting value. Returns null if not set.
     * Automatically decrypts encrypted keys.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = AppSetting::findByKey($key);

        if ($setting === null || $setting->value === null) {
            return $default;
        }

        if ($this->isEncryptedKey($key)) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                return null; // Corrupted or wrong APP_KEY
            }
        }

        return $setting->value;
    }

    /**
     * Check if a setting key has a non-null stored value.
     */
    public function has(string $key): bool
    {
        return AppSetting::hasKey($key);
    }

    /**
     * Retrieve multiple keys at once. Returns ['key' => value, ...].
     */
    public function getMany(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn ($k) => [$k => $this->get($k)])->all();
    }

    // ─── Write ───────────────────────────────────────────────────────────────

    /**
     * Store or update a setting value.
     * Automatically encrypts keys in the ENCRYPTED_KEYS list.
     *
     * @param  string  $key    Setting key
     * @param  mixed   $value  Setting value (null to clear)
     * @param  string  $group  Logical group label (e.g. 'google', 'mail')
     */
    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        $encrypted   = $this->isEncryptedKey($key);
        $storedValue = ($encrypted && $value !== null)
            ? Crypt::encryptString((string) $value)
            : ($value !== null ? (string) $value : null);

        AppSetting::updateOrCreate(
            ['key' => $key],
            [
                'value'        => $storedValue,
                'group'        => $group,
                'is_encrypted' => $encrypted,
            ]
        );
    }

    /**
     * Store multiple settings in one call.
     *
     * @param  array   $pairs   ['key' => 'value', ...]
     * @param  string  $group
     */
    public function setMany(array $pairs, string $group = 'general'): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    /**
     * Remove a setting entirely.
     */
    public function forget(string $key): void
    {
        AppSetting::where('key', $key)->delete();
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function isEncryptedKey(string $key): bool
    {
        return in_array($key, self::ENCRYPTED_KEYS, strict: true);
    }
}
