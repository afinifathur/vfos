<?php

namespace App\Services\Google;

use RuntimeException;

/**
 * Domain exception for all Google Integration errors.
 *
 * Use the error code constants to handle specific failure modes
 * in controllers and services without string matching.
 */
class GoogleApiException extends RuntimeException
{
    // ─── Error Code Constants ────────────────────────────────────────────────

    /** Google OAuth app credentials (client_id/secret) are not configured. */
    public const CONFIG_MISSING = 'config_missing';

    /** The authorization code exchange with Google failed. */
    public const EXCHANGE_FAILED = 'exchange_failed';

    /** The access token refresh attempt failed. */
    public const REFRESH_FAILED = 'refresh_failed';

    /** Stored credentials are missing or incomplete. */
    public const INVALID_CREDENTIALS = 'invalid_credentials';

    /** The stored access token is expired and no refresh token is available. */
    public const TOKEN_EXPIRED = 'token_expired';

    /** No refresh token is stored for this credential. */
    public const TOKEN_MISSING = 'token_missing';

    /** OAuth state mismatch — possible CSRF attack. */
    public const STATE_MISMATCH = 'state_mismatch';

    /** The requested provider is not supported. */
    public const UNSUPPORTED_PROVIDER = 'unsupported_provider';

    /** No connected credential found for this user + provider. */
    public const NOT_CONNECTED = 'not_connected';

    // ─── Constructor ─────────────────────────────────────────────────────────

    public function __construct(
        string $message,
        private readonly string $errorCode = self::INVALID_CREDENTIALS,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function isConfigMissing(): bool
    {
        return $this->errorCode === self::CONFIG_MISSING;
    }

    public function isTokenExpired(): bool
    {
        return $this->errorCode === self::TOKEN_EXPIRED;
    }

    public function isNotConnected(): bool
    {
        return $this->errorCode === self::NOT_CONNECTED;
    }
}
