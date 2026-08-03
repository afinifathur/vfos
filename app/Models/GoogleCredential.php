<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persists OAuth tokens for a specific Google account & provider.
 *
 * Design decisions:
 *  - client_id / client_secret are NOT stored here (they live in app_settings).
 *  - refresh_token and access_token are encrypted using Laravel's built-in
 *    `encrypted` cast (AES-256-CBC via APP_KEY).
 *  - One credential per (user_id, provider). Upserted on re-connect.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $provider       gmail | drive | calendar
 * @property string|null $google_email
 * @property string|null $refresh_token  (decrypted automatically)
 * @property string|null $access_token   (decrypted automatically)
 * @property Carbon|null $expires_at
 * @property array|null  $scopes
 * @property bool        $is_active
 * @property Carbon|null $last_connected_at
 */
class GoogleCredential extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'google_email',
        'refresh_token',
        'access_token',
        'expires_at',
        'scopes',
        'is_active',
        'last_connected_at',
    ];

    protected $casts = [
        'refresh_token'     => 'encrypted',
        'access_token'      => 'encrypted',
        'scopes'            => 'array',
        'expires_at'        => 'datetime',
        'last_connected_at' => 'datetime',
        'is_active'         => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helper Methods ─────────────────────────────────────────────────────

    /**
     * Whether the stored access token is expired (or will expire within
     * the configured refresh buffer window).
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return true; // No expiry info — treat as expired
        }

        $buffer = config('google.refresh_buffer_seconds', 300);

        return $this->expires_at->subSeconds($buffer)->isPast();
    }

    /**
     * Whether a refresh token exists (required for offline access).
     * Checks the raw attribute before decryption to handle empty string case.
     */
    public function hasRefreshToken(): bool
    {
        $raw = $this->getRawOriginal('refresh_token') ?? $this->attributes['refresh_token'] ?? null;
        if (empty($raw)) {
            return false;
        }
        // Try decrypting — if it decrypts to something non-empty, we have a token
        try {
            $decrypted = $this->refresh_token; // uses encrypted cast
            return !empty($decrypted);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Whether this credential is usable: active, has a refresh token,
     * and either has a valid access token or can be refreshed.
     */
    public function isConnected(): bool
    {
        return $this->is_active && $this->hasRefreshToken();
    }

    /**
     * Return a human-readable status label for UI display.
     */
    public function statusLabel(): string
    {
        if (!$this->is_active) {
            return 'Disconnected';
        }

        if (!$this->hasRefreshToken()) {
            return 'Incomplete';
        }

        if ($this->isExpired()) {
            return 'Token Expired';
        }

        return 'Connected';
    }
}
