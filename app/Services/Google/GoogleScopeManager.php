<?php

namespace App\Services\Google;

/**
 * Central registry for Google OAuth scopes.
 *
 * All scope definitions live here — no scattered strings in controllers/services.
 * When a new Google module is added (e.g. Google Sheets), add its scopes here.
 *
 * Usage:
 *   $manager = new GoogleScopeManager();
 *   $scopes  = $manager->getScopesForProvider('gmail');
 *   // → ['openid', '...email', '...profile', '...gmail.readonly']
 */
class GoogleScopeManager
{
    /**
     * Scopes always included regardless of provider.
     * Required to retrieve the connected Google account's email address.
     */
    private const BASE_SCOPES = [
        'openid',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ];

    /**
     * Additional scopes per provider, merged on top of BASE_SCOPES.
     *
     * Future modules register their scopes here without touching any other file.
     * Phase 1: only 'gmail' is wired to the UI. Drive/Calendar are pre-registered
     * for future use but not yet exposed to the user.
     */
    private const PROVIDER_SCOPES = [
        'gmail' => [
            'https://www.googleapis.com/auth/gmail.readonly',
        ],
        'drive' => [
            'https://www.googleapis.com/auth/drive.readonly',
        ],
        'calendar' => [
            'https://www.googleapis.com/auth/calendar.readonly',
        ],
    ];

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Return the merged scope list for a given provider.
     *
     * @throws GoogleApiException if provider is not registered
     */
    public function getScopesForProvider(string $provider): array
    {
        if (!$this->isProviderSupported($provider)) {
            throw new GoogleApiException(
                "Provider [{$provider}] is not registered in GoogleScopeManager.",
                GoogleApiException::UNSUPPORTED_PROVIDER
            );
        }

        return array_unique(
            array_merge(self::BASE_SCOPES, self::PROVIDER_SCOPES[$provider])
        );
    }

    /**
     * Return only the base scopes (openid, email, profile).
     * Useful when building a minimal token purely for user identification.
     */
    public function getBaseScopes(): array
    {
        return self::BASE_SCOPES;
    }

    /**
     * Return the list of supported provider slugs.
     */
    public function getSupportedProviders(): array
    {
        return array_keys(self::PROVIDER_SCOPES);
    }

    /**
     * Check if a provider slug is registered.
     */
    public function isProviderSupported(string $provider): bool
    {
        return array_key_exists($provider, self::PROVIDER_SCOPES);
    }

    /**
     * Return a human-readable label for a provider slug.
     */
    public function getProviderLabel(string $provider): string
    {
        return config("google.providers.{$provider}", ucfirst($provider));
    }
}
