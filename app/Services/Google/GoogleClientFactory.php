<?php

namespace App\Services\Google;

use App\Repositories\AppSettingRepository;
use Google\Client;

/**
 * Builds a configured Google\Client instance.
 *
 * ARCHITECTURAL CONTRACT:
 *  - Client ID and Secret are read from AppSettingRepository (application settings).
 *  - They are NEVER read from .env directly.
 *  - They are NEVER read from google_credentials (that table stores user tokens only).
 *  - Redirect URI is fixed from config('google.redirect_uri').
 *  - Scopes are resolved by GoogleScopeManager, never hardcoded here.
 *
 * This factory is the ONLY place that instantiates Google\Client.
 * All services receive a configured client from here.
 */
class GoogleClientFactory
{
    public function __construct(
        private readonly AppSettingRepository $settings,
        private readonly GoogleScopeManager $scopeManager
    ) {}

    /**
     * Build a Google\Client configured for the given provider.
     *
     * @param  string  $provider  e.g. 'gmail', 'drive', 'calendar'
     *
     * @throws GoogleApiException if client_id or client_secret is missing
     * @throws GoogleApiException if provider is unsupported
     */
    public function make(string $provider = 'gmail'): Client
    {
        $clientId     = $this->settings->get('google_client_id');
        $clientSecret = $this->settings->get('google_client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            throw new GoogleApiException(
                'Google OAuth credentials are not configured. '
                . 'Please add your Client ID and Client Secret in Settings → Google.',
                GoogleApiException::CONFIG_MISSING
            );
        }

        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $redirectUri = config('google.redirect_uri');
        $client->setRedirectUri(!empty($redirectUri) ? $redirectUri : 'http://vfos.test/auth/google/callback');

        // 'offline' access type is required to receive a refresh token.
        $client->setAccessType('offline');

        // 'consent' prompt forces Google to return a refresh token on every authorization,
        // even if the user has previously authorized the same app.
        $client->setPrompt('consent select_account');

        // Include scopes already granted in previous authorizations.
        $client->setIncludeGrantedScopes(true);

        // Resolve and set provider scopes
        $scopes = $this->scopeManager->getScopesForProvider($provider);
        $client->setScopes($scopes);

        return $client;
    }

    /**
     * Build a Google\Client with an existing access token already set.
     * Used for API calls that require an authenticated client.
     *
     * @param  string  $provider
     * @param  array   $tokenArray  The token array (from stored JSON or token exchange)
     */
    public function makeWithToken(string $provider, array $tokenArray): Client
    {
        $client = $this->make($provider);
        $client->setAccessToken($tokenArray);

        return $client;
    }
}
