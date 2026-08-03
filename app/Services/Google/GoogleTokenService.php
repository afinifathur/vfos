<?php

namespace App\Services\Google;

use App\Models\GoogleCredential;
use App\Repositories\GoogleCredentialRepository;
use Carbon\Carbon;

/**
 * Manages secure storage and retrieval of Google OAuth tokens.
 *
 * Responsibilities:
 *  - Store a fresh token set returned by Google after OAuth code exchange
 *  - Update access token after a successful refresh
 *  - Encrypt sensitive fields (handled automatically by GoogleCredential model casts)
 *
 * This service does NOT make any Google API calls.
 * API calls are made by GoogleOAuthService (token exchange/refresh)
 * or by future feature-specific services (GmailService, DriveService, etc.)
 */
class GoogleTokenService
{
    public function __construct(
        private readonly GoogleCredentialRepository $credentialRepo,
        private readonly GoogleScopeManager $scopeManager
    ) {}

    /**
     * Persist the full token set returned by Google after code exchange.
     *
     * @param  int     $userId       The authenticated vFOS user's ID
     * @param  string  $provider     'gmail' | 'drive' | 'calendar'
     * @param  array   $tokenData    Raw token array from Google\Client::fetchAccessTokenWithAuthCode()
     * @param  string|null $googleEmail  The Google account's email address
     *
     * @return GoogleCredential  The upserted credential record
     */
    public function storeTokens(
        int $userId,
        string $provider,
        array $tokenData,
        ?string $googleEmail = null
    ): GoogleCredential {
        $expiresAt = isset($tokenData['expires_in'])
            ? now()->addSeconds((int) $tokenData['expires_in'])
            : null;

        return $this->credentialRepo->upsert([
            'user_id'       => $userId,
            'provider'      => $provider,
            'google_email'  => $googleEmail,
            'access_token'  => $tokenData['access_token'] ?? null,
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_at'    => $expiresAt,
            'scopes'        => $this->parseScopesFromToken($tokenData)
                                 ?? $this->scopeManager->getScopesForProvider($provider),
        ]);
    }

    /**
     * Update only the access token and its expiry on an existing credential.
     * Called after a successful token refresh (refresh token remains unchanged).
     *
     * @param  GoogleCredential  $credential   The credential to update
     * @param  array             $tokenData    Fresh token data from Google
     *
     * @return GoogleCredential  Updated credential
     */
    public function updateAccessToken(
        GoogleCredential $credential,
        array $tokenData
    ): GoogleCredential {
        $expiresAt = isset($tokenData['expires_in'])
            ? now()->addSeconds((int) $tokenData['expires_in'])
            : null;

        return $this->credentialRepo->updateAccessToken(
            $credential,
            $tokenData['access_token'],
            Carbon::parse($expiresAt)
        );
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    /**
     * Extract the scopes array from the token data.
     * Google returns scopes as a space-separated string in 'scope'.
     */
    private function parseScopesFromToken(array $tokenData): ?array
    {
        if (empty($tokenData['scope'])) {
            return null;
        }

        return explode(' ', trim($tokenData['scope']));
    }
}
