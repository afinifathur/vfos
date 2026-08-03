<?php

namespace App\Repositories;

use App\Models\GoogleCredential;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Encapsulates all database interactions for google_credentials.
 *
 * Controllers and Services must never query GoogleCredential directly —
 * all reads/writes go through this repository.
 */
class GoogleCredentialRepository
{
    /**
     * Find the active credential for a user + provider.
     * Returns null if no credential exists.
     */
    public function findByUserAndProvider(int $userId, string $provider): ?GoogleCredential
    {
        return GoogleCredential::where('user_id', $userId)
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Return all credentials for a user (across all providers).
     */
    public function allForUser(int $userId): Collection
    {
        return GoogleCredential::where('user_id', $userId)->get();
    }

    /**
     * Create or update the credential for a user + provider.
     *
     * @param  array  $data  Keys: user_id, provider, google_email,
     *                       refresh_token, access_token, expires_at,
     *                       scopes, is_active, last_connected_at
     */
    public function upsert(array $data): GoogleCredential
    {
        $updates = array_merge(
            array_filter($data, fn($v) => $v !== null),
            [
                'is_active'         => true,
                'last_connected_at' => now(),
            ]
        );

        $credential = GoogleCredential::updateOrCreate(
            [
                'user_id'  => $data['user_id'],
                'provider' => $data['provider'],
            ],
            $updates
        );

        return $credential->fresh();
    }

    /**
     * Update only the access token and expiry for an existing credential.
     * Used when refreshing a token without changing the refresh token.
     */
    public function updateAccessToken(
        GoogleCredential $credential,
        string $accessToken,
        Carbon $expiresAt
    ): GoogleCredential {
        $credential->update([
            'access_token' => $accessToken,
            'expires_at'   => $expiresAt,
        ]);

        return $credential->fresh();
    }

    /**
     * Soft-disconnect: mark credential as inactive, clear tokens.
     * Does NOT delete the row (preserves audit trail + google_email).
     */
    public function deactivate(int $userId, string $provider): bool
    {
        return (bool) GoogleCredential::where('user_id', $userId)
            ->where('provider', $provider)
            ->update([
                'is_active'     => false,
                'access_token'  => null,
                'refresh_token' => null,
                'expires_at'    => null,
            ]);
    }

    /**
     * Hard-delete credential record for a user + provider.
     */
    public function delete(int $userId, string $provider): bool
    {
        return (bool) GoogleCredential::where('user_id', $userId)
            ->where('provider', $provider)
            ->delete();
    }
}
