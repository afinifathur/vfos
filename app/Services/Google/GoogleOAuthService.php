<?php

namespace App\Services\Google;

use App\Models\GoogleCredential;
use App\Repositories\GoogleCredentialRepository;
use Illuminate\Support\Str;

/**
 * Handles Google OAuth 2.0 authorization flow.
 *
 * Responsibilities:
 *  1. Build the authorization URL for redirecting the user to Google
 *  2. Exchange an authorization code for access + refresh tokens
 *  3. Refresh an expired access token using the stored refresh token
 *  4. Disconnect (deactivate) a credential
 *
 * This service does NOT:
 *  - Read Gmail messages
 *  - Make any Google API calls beyond token operations and userinfo
 *  - Interact with the Finance module
 */
class GoogleOAuthService
{
    public function __construct(
        private readonly GoogleClientFactory $factory,
        private readonly GoogleTokenService $tokenService,
        private readonly GoogleCredentialRepository $credentialRepo
    ) {}

    // ─── Step 1: Authorization URL ───────────────────────────────────────────

    /**
     * Generate the Google OAuth authorization URL and store the CSRF state in session.
     *
     * @param  string  $provider  'gmail' | 'drive' | 'calendar'
     * @return string  The URL to redirect the user to
     *
     * @throws GoogleApiException if credentials are not configured
     */
    public function getAuthorizationUrl(string $provider = 'gmail'): string
    {
        $client = $this->factory->make($provider);

        // Generate a cryptographically random state for CSRF protection
        $state = Str::random(40);

        // Store in session for validation in callback
        session([
            'google_oauth_state'    => $state,
            'google_oauth_provider' => $provider,
        ]);

        $client->setState($state);

        return $client->createAuthUrl();
    }

    // ─── Step 2: Code Exchange ───────────────────────────────────────────────

    /**
     * Exchange an authorization code (from Google's callback) for OAuth tokens.
     * Validates the CSRF state, fetches tokens, retrieves user info, and persists.
     *
     * @param  string  $code    The authorization code from Google callback
     * @param  string  $state   The state parameter echoed back by Google
     * @param  int     $userId  The authenticated vFOS user's ID
     *
     * @return GoogleCredential  The persisted credential with encrypted tokens
     *
     * @throws GoogleApiException on state mismatch, exchange failure, or API error
     */
    public function exchangeAuthorizationCode(
        string $code,
        string $state,
        int $userId
    ): GoogleCredential {
        // Retrieve and immediately consume CSRF state and provider from session
        $expectedState = session('google_oauth_state');
        $provider      = session('google_oauth_provider', 'gmail');

        session()->forget(['google_oauth_state', 'google_oauth_provider']);

        // CSRF validation
        if (!$expectedState || !hash_equals($expectedState, $state)) {
            throw new GoogleApiException(
                'OAuth state mismatch or session expired. Please ensure browser cookies are enabled and initiate the connection again.',
                GoogleApiException::STATE_MISMATCH
            );
        }

        $client = $this->factory->make($provider);

        // Exchange the authorization code for tokens
        try {
            $tokenData = $client->fetchAccessTokenWithAuthCode($code);
        } catch (\Exception $e) {
            throw new GoogleApiException(
                'Failed to exchange authorization code with Google: ' . $e->getMessage(),
                GoogleApiException::EXCHANGE_FAILED,
                $e
            );
        }

        // Check for error in the token response
        if (isset($tokenData['error'])) {
            throw new GoogleApiException(
                'Google returned an error: ' . ($tokenData['error_description'] ?? $tokenData['error']),
                GoogleApiException::EXCHANGE_FAILED
            );
        }

        // Retrieve the connected Google account's email via userinfo
        $googleEmail = $this->fetchGoogleEmail($client, $tokenData);

        // Store tokens securely via TokenService
        return $this->tokenService->storeTokens($userId, $provider, $tokenData, $googleEmail);
    }

    // ─── Step 3: Token Refresh ───────────────────────────────────────────────

    /**
     * Refresh the access token for an existing credential using its refresh token.
     *
     * @param  GoogleCredential  $credential
     * @return GoogleCredential  Updated credential with fresh access token
     *
     * @throws GoogleApiException if no refresh token is stored or refresh fails
     */
    public function refreshAccessToken(GoogleCredential $credential): GoogleCredential
    {
        if (!$credential->hasRefreshToken()) {
            throw new GoogleApiException(
                'Cannot refresh: no refresh token stored for this credential.',
                GoogleApiException::TOKEN_MISSING
            );
        }

        $client = $this->factory->make($credential->provider);
        $newTokenData = null;

        try {
            $client->refreshToken($credential->refresh_token);
            $newTokenData = $client->getAccessToken();
        } catch (\Throwable $e) {
            if ($this->isInvalidGrant(null, $e)) {
                $this->handleInvalidGrant($credential, $e->getMessage());
            }

            throw new GoogleApiException(
                'Token refresh request failed with Google: ' . $e->getMessage(),
                GoogleApiException::REFRESH_FAILED,
                $e
            );
        }

        if (is_array($newTokenData) && isset($newTokenData['error'])) {
            $error     = (string) $newTokenData['error'];
            $errorDesc = $newTokenData['error_description'] ?? $error;

            if ($this->isInvalidGrant($newTokenData)) {
                $this->handleInvalidGrant($credential, (string) $errorDesc);
            }

            throw new GoogleApiException(
                "Token refresh failed [{$error}]: {$errorDesc}. Re-authorization is required.",
                GoogleApiException::REFRESH_FAILED
            );
        }

        if (empty($newTokenData['access_token'])) {
            throw new GoogleApiException(
                'Token refresh failed: Google did not return a new access token.',
                GoogleApiException::REFRESH_FAILED
            );
        }

        return $this->tokenService->updateAccessToken($credential, $newTokenData);
    }

    // ─── Step 4: Disconnect ──────────────────────────────────────────────────

    /**
     * Disconnect a Google account. Deactivates the credential row without deleting it.
     *
     * @param  int     $userId
     * @param  string  $provider
     */
    public function disconnect(int $userId, string $provider = 'gmail'): void
    {
        $this->credentialRepo->deactivate($userId, $provider);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Determine specifically whether the failure was caused by Google's invalid_grant.
     */
    private function isInvalidGrant(?array $tokenData, ?\Throwable $e = null): bool
    {
        if (is_array($tokenData)) {
            $error = strtolower((string) ($tokenData['error'] ?? ''));
            $desc  = strtolower((string) ($tokenData['error_description'] ?? ''));
            if ($error === 'invalid_grant' || str_contains($error, 'invalid_grant') || str_contains($desc, 'invalid_grant')) {
                return true;
            }
        }

        if ($e !== null) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'invalid_grant')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deactivate the credential when the refresh token is definitively rejected by Google.
     * Scoped strictly to this user's credential.
     */
    private function handleInvalidGrant(GoogleCredential $credential, string $reason): void
    {
        \Illuminate\Support\Facades\Log::warning('Google refresh token rejected with invalid_grant. Deactivating credential.', [
            'user_id'      => $credential->user_id,
            'provider'     => $credential->provider,
            'google_email' => $credential->google_email,
            'reason'       => $reason,
        ]);

        $this->credentialRepo->deactivate($credential->user_id, $credential->provider);
    }

    /**
     * Fetch the Google account email using the OAuth2 userinfo endpoint.
     * Sets the access token on the client before making the call.
     */
    private function fetchGoogleEmail(\Google\Client $client, array $tokenData): ?string
    {
        try {
            $client->setAccessToken($tokenData);
            $oauth2    = new \Google\Service\Oauth2($client);
            $userInfo  = $oauth2->userinfo->get();
            return $userInfo->getEmail();
        } catch (\Exception) {
            // Non-fatal: we can proceed without the email
            return null;
        }
    }
}
