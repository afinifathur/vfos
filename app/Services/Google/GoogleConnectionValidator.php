<?php

namespace App\Services\Google;

use App\Models\GoogleCredential;
use App\Repositories\AppSettingRepository;
use App\Repositories\GoogleCredentialRepository;

/**
 * Validates the completeness and health of a Google credential.
 *
 * Used by:
 *  - Settings page: display "Connected" / "Not Connected" status
 *  - Future services: guard before making API calls
 *
 * Returns structured status arrays suitable for passing directly to Blade views.
 */
class GoogleConnectionValidator
{
    public function __construct(
        private readonly GoogleCredentialRepository $credentialRepo,
        private readonly AppSettingRepository $appSettings,
        private readonly GoogleScopeManager $scopeManager
    ) {}

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Get the required scopes for a given provider.
     */
    public function requiredScopes(string $provider): array
    {
        return $this->scopeManager->getScopesForProvider($provider);
    }

    /**
     * Get the missing scopes for a credential.
     */
    public function missingScopes(GoogleCredential $credential, string $provider): array
    {
        $required = $this->requiredScopes($provider);
        $granted = $credential->scopes ?? [];

        return array_values(array_diff($required, $granted));
    }

    /**
     * Check if a credential has all required scopes for a provider.
     */
    public function hasRequiredScopes(GoogleCredential $credential, string $provider): bool
    {
        return empty($this->missingScopes($credential, $provider));
    }

    /**
     * Check if the Google OAuth app is configured (client_id + secret present).
     */
    public function isAppConfigured(): bool
    {
        return $this->appSettings->has('google_client_id')
            && $this->appSettings->has('google_client_secret');
    }

    /**
     * Check if a given credential is valid and ready for API use.
     *
     * A credential is valid when:
     *  - It exists and is active
     *  - It has a refresh token (required for offline access)
     *  - It has a valid (non-expired) access token OR can be refreshed
     */
    public function validate(GoogleCredential $credential): bool
    {
        return $credential->is_active
            && $credential->hasRefreshToken()
            && (!$credential->isExpired() || $credential->hasRefreshToken());
    }

    /**
     * Return a structured status array for a user + provider combination.
     * Safe to pass directly to Blade views.
     *
     * @param  int     $userId
     * @param  string  $provider
     * @return array{
     *   connected: bool,
     *   google_email: string|null,
     *   status_label: string,
     *   status_color: string,
     *   last_connected_at: string|null,
     *   is_expired: bool,
     *   has_refresh_token: bool,
     *   app_configured: bool,
     *   granted_scopes: array,
     *   required_scopes: array,
     *   missing_scopes: array,
     *   has_required_scopes: bool
     * }
     */
    public function getStatus(int $userId, string $provider = 'gmail'): array
    {
        $appConfigured = $this->isAppConfigured();
        $credential    = $this->credentialRepo->findByUserAndProvider($userId, $provider);

        if (!$credential || !$credential->is_active) {
            return [
                'connected'           => false,
                'google_email'        => null,
                'status_label'        => 'Not Connected',
                'status_color'        => 'slate',
                'last_connected_at'   => null,
                'is_expired'          => false,
                'has_refresh_token'   => false,
                'app_configured'      => $appConfigured,
                'granted_scopes'      => [],
                'required_scopes'     => $this->requiredScopes($provider),
                'missing_scopes'      => $this->requiredScopes($provider),
                'has_required_scopes' => false,
            ];
        }

        $isExpired = $credential->isExpired();
        $hasToken  = $credential->hasRefreshToken();
        $connected = $credential->is_active && $hasToken;

        $hasRequiredScopes = $this->hasRequiredScopes($credential, $provider);

        if ($connected && !$hasRequiredScopes) {
            $statusLabel = 'Reconnect Required';
            $statusColor = 'red';
        } elseif ($connected && $isExpired && $hasToken) {
            $statusLabel = 'Connected (token refreshable)';
            $statusColor = 'yellow';
        } elseif ($connected && !$isExpired) {
            $statusLabel = 'Connected';
            $statusColor = 'green';
        } elseif (!$hasToken) {
            $statusLabel = 'Incomplete';
            $statusColor = 'orange';
        } else {
            $statusLabel = 'Disconnected';
            $statusColor = 'red';
        }

        return [
            'connected'           => $connected,
            'google_email'        => $credential->google_email,
            'status_label'        => $statusLabel,
            'status_color'        => $statusColor,
            'last_connected_at'   => $credential->last_connected_at?->diffForHumans(),
            'is_expired'          => $isExpired,
            'has_refresh_token'   => $hasToken,
            'app_configured'      => $appConfigured,
            'granted_scopes'      => $credential->scopes ?? [],
            'required_scopes'     => $this->requiredScopes($provider),
            'missing_scopes'      => $this->missingScopes($credential, $provider),
            'has_required_scopes' => $hasRequiredScopes,
        ];
    }

    /**
     * Return status for all supported providers for a user.
     *
     * @param  int    $userId
     * @param  array  $providers  ['gmail', 'drive', 'calendar']
     * @return array<string, array>
     */
    public function getStatusForAll(int $userId, array $providers = ['gmail', 'drive', 'calendar']): array
    {
        return collect($providers)
            ->mapWithKeys(fn ($p) => [$p => $this->getStatus($userId, $p)])
            ->all();
    }
}
