<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Handles Google OAuth 2.0 authorization flow for vFOS.
 *
 * Routes:
 *   GET  /auth/google/connect      → connect()
 *   GET  /auth/google/callback     → callback()
 *   POST /auth/google/disconnect   → disconnect()
 *
 * All routes are protected by auth middleware.
 * No Gmail API calls are made in this controller.
 */
class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleOAuthService $oAuthService
    ) {}

    // ─── Connect ─────────────────────────────────────────────────────────────

    /**
     * Redirect the user to Google's authorization page.
     *
     * If the Google OAuth app is not configured (missing client_id or secret),
     * redirects back to the settings page with an error.
     *
     * @param  Request  $request  Optional ?provider= query param (default: gmail)
     */
    public function connect(Request $request): RedirectResponse
    {
        $provider = $request->get('provider', 'gmail');

        try {
            $authUrl = $this->oAuthService->getAuthorizationUrl($provider);
            return redirect()->away($authUrl);
        } catch (GoogleApiException $e) {
            Log::warning('Google connect failed', [
                'user_id'    => Auth::id(),
                'provider'   => $provider,
                'error_code' => $e->getErrorCode(),
                'message'    => $e->getMessage(),
            ]);

            return redirect()
                ->to(route('settings.index') . '#google')
                ->withErrors(['google' => $e->getMessage()]);
        }
    }

    // ─── Callback ────────────────────────────────────────────────────────────

    /**
     * Handle the OAuth callback from Google.
     *
     * Google redirects here with ?code= and ?state= after the user
     * authorizes (or denies) the application.
     */
    public function callback(Request $request): RedirectResponse
    {
        // User denied access on Google's consent screen
        if ($request->has('error')) {
            $error = $request->get('error', 'unknown_error');
            Log::info('Google OAuth denied by user', [
                'user_id' => Auth::id(),
                'error'   => $error,
            ]);

            return redirect()
                ->to(route('settings.index') . '#google')
                ->withErrors(['google' => 'Google authorization was denied: ' . $error]);
        }

        $code  = $request->get('code');
        $state = $request->get('state', '');

        if (empty($code)) {
            return redirect()
                ->to(route('settings.index') . '#google')
                ->withErrors(['google' => 'No authorization code received from Google.']);
        }

        try {
            $credential = $this->oAuthService->exchangeAuthorizationCode(
                code:   $code,
                state:  $state,
                userId: Auth::id()
            );

            if (!$credential->hasRefreshToken()) {
                Log::warning('Google account connected without refresh token', [
                    'user_id'      => Auth::id(),
                    'provider'     => $credential->provider,
                    'google_email' => $credential->google_email,
                ]);

                return redirect()
                    ->to(route('settings.index') . '#google')
                    ->with('warning', "Google account ({$credential->google_email}) connected, but offline access (refresh token) was not granted by Google. Please disconnect and re-authenticate, ensuring you grant offline access.");
            }

            Log::info('Google account connected', [
                'user_id'      => Auth::id(),
                'provider'     => $credential->provider,
                'google_email' => $credential->google_email,
            ]);

            return redirect()
                ->to(route('settings.index') . '#google')
                ->with('success', "Google account ({$credential->google_email}) connected successfully.");

        } catch (GoogleApiException $e) {
            Log::error('Google OAuth callback failed', [
                'user_id'    => Auth::id(),
                'error_code' => $e->getErrorCode(),
                'message'    => $e->getMessage(),
            ]);

            return redirect()
                ->to(route('settings.index') . '#google')
                ->withErrors(['google' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    // ─── Disconnect ──────────────────────────────────────────────────────────

    /**
     * Disconnect the Google account for the authenticated user.
     *
     * Accepts optional ?provider= query param (default: gmail).
     * Uses POST to prevent CSRF via accidental GET requests.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $provider = $request->get('provider', 'gmail');

        $this->oAuthService->disconnect(Auth::id(), $provider);

        Log::info('Google account disconnected', [
            'user_id'  => Auth::id(),
            'provider' => $provider,
        ]);

        return redirect()
            ->to(route('settings.index') . '#google')
            ->with('success', 'Google account disconnected.');
    }
}
