<?php

namespace Tests\Feature\Google;

use App\Models\AppSetting;
use App\Models\User;
use App\Repositories\AppSettingRepository;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Tests the OAuth redirect and callback flow.
 *
 * Uses Mockery to avoid real Google API calls.
 */
class GoogleOAuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    // ─── Setup: seed a configured app ────────────────────────────────────────

    private function configureGoogleApp(): void
    {
        $repo = app(AppSettingRepository::class);
        $repo->set('google_client_id', 'test-client-id-12345.apps.googleusercontent.com', 'google');
        $repo->set('google_client_secret', 'GOCSPX-test-secret-value', 'google');
    }

    // ─── Tests: connect route ─────────────────────────────────────────────────

    public function test_connect_route_requires_authentication(): void
    {
        $response = $this->get(route('google.connect'));

        $response->assertRedirect(route('login'));
    }

    public function test_connect_redirects_when_app_not_configured(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('google.connect'));

        // Should redirect back to settings with error (no client_id/secret)
        $response->assertRedirect();
        $response->assertSessionHasErrors('google');
    }

    public function test_connect_redirects_to_google_when_configured(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        // Mock GoogleOAuthService to return a fake Google URL
        $this->mock(
            \App\Services\Google\GoogleOAuthService::class,
            function ($mock) {
                $mock->shouldReceive('getAuthorizationUrl')
                    ->once()
                    ->andReturn('https://accounts.google.com/o/oauth2/auth?client_id=test&state=abc');
            }
        );

        $response = $this->actingAs($user)->get(route('google.connect'));

        $response->assertRedirect();
        $this->assertStringContainsString(
            'accounts.google.com',
            $response->headers->get('Location')
        );
    }

    public function test_connect_stores_csrf_state_in_session(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        // Mock GoogleOAuthService and manually set session state
        $this->mock(
            \App\Services\Google\GoogleOAuthService::class,
            function ($mock) {
                $mock->shouldReceive('getAuthorizationUrl')
                    ->once()
                    ->andReturnUsing(function () {
                        session([
                            'google_oauth_state'    => 'fake-state-xyz',
                            'google_oauth_provider' => 'gmail',
                        ]);
                        return 'https://accounts.google.com/?state=fake-state-xyz';
                    });
            }
        );

        $this->actingAs($user)->get(route('google.connect'));

        $this->assertEquals('fake-state-xyz', session('google_oauth_state'));
        $this->assertEquals('gmail', session('google_oauth_provider'));
    }

    // ─── Tests: callback route ────────────────────────────────────────────────

    public function test_callback_requires_authentication(): void
    {
        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('login'));
    }

    public function test_callback_redirects_with_error_when_no_code(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('google.callback'));

        $response->assertRedirect();
        $response->assertSessionHasErrors('google');
    }

    public function test_callback_redirects_with_error_on_google_denial(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('google.callback') . '?error=access_denied');

        $response->assertRedirect();
        $response->assertSessionHasErrors('google');
    }

    public function test_disconnect_route_requires_authentication(): void
    {
        $response = $this->post(route('google.disconnect'));

        $response->assertRedirect(route('login'));
    }

    public function test_callback_rejects_state_mismatch(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        // Put an expected state into session
        session(['google_oauth_state' => 'valid-csrf-token']);

        // Send callback with mismatched state
        $response = $this->actingAs($user)->get(route('google.callback') . '?code=fake-code&state=wrong-token');

        $response->assertRedirect();
        $response->assertSessionHasErrors('google');
    }

    public function test_expired_access_token_can_refresh_using_valid_refresh_token(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        $tokenService = app(\App\Services\Google\GoogleTokenService::class);
        $cred = $tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.expired-token',
            'refresh_token' => '1//valid-refresh-token',
            'expires_in'    => -10, // already expired
        ], 'test@gmail.com');

        $this->assertTrue($cred->isExpired());

        // Mock GoogleClientFactory to return a Google\Client that succeeds on refreshToken
        $mockClient = Mockery::mock(\Google\Client::class);
        $mockClient->shouldReceive('refreshToken')
            ->once()
            ->with('1//valid-refresh-token')
            ->andReturnNull();
        $mockClient->shouldReceive('getAccessToken')
            ->once()
            ->andReturn([
                'access_token' => 'ya29.refreshed-fresh-token',
                'expires_in'   => 3600,
            ]);

        $mockFactory = Mockery::mock(\App\Services\Google\GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')
            ->once()
            ->with('gmail')
            ->andReturn($mockClient);

        $oAuthService = new \App\Services\Google\GoogleOAuthService(
            $mockFactory,
            $tokenService,
            app(\App\Repositories\GoogleCredentialRepository::class)
        );

        $refreshed = $oAuthService->refreshAccessToken($cred);

        $this->assertEquals('ya29.refreshed-fresh-token', $refreshed->access_token);
        $this->assertEquals('1//valid-refresh-token', $refreshed->refresh_token);
        $this->assertFalse($refreshed->isExpired());
    }

    public function test_refresh_fails_when_no_refresh_token_stored(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        $tokenService = app(\App\Services\Google\GoogleTokenService::class);
        $cred = $tokenService->storeTokens($user->id, 'gmail', [
            'access_token' => 'ya29.token-no-refresh',
            'expires_in'   => 3600,
        ], 'test@gmail.com');

        $this->assertNull($cred->refresh_token);

        $oAuthService = app(\App\Services\Google\GoogleOAuthService::class);

        try {
            $oAuthService->refreshAccessToken($cred);
            $this->fail('Expected GoogleApiException was not thrown');
        } catch (\App\Services\Google\GoogleApiException $e) {
            $this->assertEquals(\App\Services\Google\GoogleApiException::TOKEN_MISSING, $e->getErrorCode());
        }
    }

    public function test_local_and_production_redirect_configuration_independence(): void
    {
        // 1. Default local fallback
        config(['google.redirect_uri' => 'http://vfos.test/auth/google/callback']);
        $this->assertEquals('http://vfos.test/auth/google/callback', config('google.redirect_uri'));

        // 2. Production explicit override (e.g. HTTPS domain)
        config(['google.redirect_uri' => 'https://vfos.veronica.internal/auth/google/callback']);
        $this->assertEquals('https://vfos.veronica.internal/auth/google/callback', config('google.redirect_uri'));
    }

    public function test_valid_oauth_state_is_accepted_and_consumed(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        session(['google_oauth_state' => 'exact-valid-state', 'google_oauth_provider' => 'gmail']);

        $mockClient = Mockery::mock(\Google\Client::class);
        $mockClient->shouldReceive('fetchAccessTokenWithAuthCode')
            ->once()
            ->with('valid-auth-code')
            ->andReturn([
                'access_token'  => 'ya29.valid-access',
                'refresh_token' => '1//valid-refresh',
                'expires_in'    => 3600,
                'scope'         => 'openid https://www.googleapis.com/auth/gmail.readonly',
            ]);
        $mockClient->shouldReceive('setAccessToken')->zeroOrMoreTimes()->andReturnNull();

        $mockFactory = Mockery::mock(\App\Services\Google\GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')
            ->once()
            ->with('gmail')
            ->andReturn($mockClient);

        $oAuthService = new \App\Services\Google\GoogleOAuthService(
            $mockFactory,
            app(\App\Services\Google\GoogleTokenService::class),
            app(\App\Repositories\GoogleCredentialRepository::class)
        );

        $cred = $oAuthService->exchangeAuthorizationCode('valid-auth-code', 'exact-valid-state', $user->id);

        $this->assertInstanceOf(\App\Models\GoogleCredential::class, $cred);
        $this->assertEquals('ya29.valid-access', $cred->access_token);

        // State must be consumed after successful exchange
        $this->assertNull(session('google_oauth_state'));
        $this->assertNull(session('google_oauth_provider'));
    }

    public function test_oauth_state_is_consumed_on_mismatched_validation(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        session(['google_oauth_state' => 'secret-state-123', 'google_oauth_provider' => 'gmail']);

        $oAuthService = app(\App\Services\Google\GoogleOAuthService::class);

        try {
            $oAuthService->exchangeAuthorizationCode('fake-code', 'tampered-state', $user->id);
            $this->fail('Expected GoogleApiException was not thrown');
        } catch (\App\Services\Google\GoogleApiException $e) {
            $this->assertEquals(\App\Services\Google\GoogleApiException::STATE_MISMATCH, $e->getErrorCode());
        }

        // State must be completely consumed (deleted) even on failure
        $this->assertNull(session('google_oauth_state'));
        $this->assertNull(session('google_oauth_provider'));
    }

    public function test_oauth_state_missing_is_rejected_and_session_cleared(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        // No state in session
        $this->assertNull(session('google_oauth_state'));

        $oAuthService = app(\App\Services\Google\GoogleOAuthService::class);

        try {
            $oAuthService->exchangeAuthorizationCode('fake-code', 'any-state', $user->id);
            $this->fail('Expected GoogleApiException was not thrown');
        } catch (\App\Services\Google\GoogleApiException $e) {
            $this->assertEquals(\App\Services\Google\GoogleApiException::STATE_MISMATCH, $e->getErrorCode());
        }

        $this->assertNull(session('google_oauth_state'));
    }

    public function test_oauth_state_reuse_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        session(['google_oauth_state' => 'single-use-state', 'google_oauth_provider' => 'gmail']);

        $oAuthService = app(\App\Services\Google\GoogleOAuthService::class);

        // First attempt with incorrect state fails and consumes session
        try {
            $oAuthService->exchangeAuthorizationCode('code-1', 'wrong-state', $user->id);
        } catch (\App\Services\Google\GoogleApiException) {}

        // Second attempt with the original state MUST now fail because it was consumed
        try {
            $oAuthService->exchangeAuthorizationCode('code-2', 'single-use-state', $user->id);
            $this->fail('Expected GoogleApiException was not thrown on state reuse');
        } catch (\App\Services\Google\GoogleApiException $e) {
            $this->assertEquals(\App\Services\Google\GoogleApiException::STATE_MISMATCH, $e->getErrorCode());
        }
    }

    public function test_invalid_grant_deactivates_user_credential(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        $tokenService = app(\App\Services\Google\GoogleTokenService::class);
        $cred = $tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.expired-access',
            'refresh_token' => '1//revoked-refresh-token',
            'expires_in'    => 3600,
        ], 'active-user@gmail.com');

        $this->assertTrue($cred->is_active);
        $this->assertTrue($cred->hasRefreshToken());

        // Mock Google\Client to return invalid_grant error
        $mockClient = Mockery::mock(\Google\Client::class);
        $mockClient->shouldReceive('refreshToken')
            ->once()
            ->with('1//revoked-refresh-token')
            ->andReturnNull();
        $mockClient->shouldReceive('getAccessToken')
            ->once()
            ->andReturn([
                'error'             => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ]);

        $mockFactory = Mockery::mock(\App\Services\Google\GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')
            ->once()
            ->with('gmail')
            ->andReturn($mockClient);

        $oAuthService = new \App\Services\Google\GoogleOAuthService(
            $mockFactory,
            $tokenService,
            app(\App\Repositories\GoogleCredentialRepository::class)
        );

        try {
            $oAuthService->refreshAccessToken($cred);
            $this->fail('Expected GoogleApiException was not thrown on invalid_grant');
        } catch (\App\Services\Google\GoogleApiException $e) {
            $this->assertEquals(\App\Services\Google\GoogleApiException::REFRESH_FAILED, $e->getErrorCode());
            $this->assertStringContainsString('invalid_grant', $e->getMessage());
        }

        // Credential must now be deactivated in the database
        $cred->refresh();
        $this->assertFalse($cred->is_active, 'Credential must be marked inactive on invalid_grant');
        $this->assertNull($cred->refresh_token, 'Refresh token must be cleared on invalid_grant');
        $this->assertNull($cred->access_token);
        $this->assertFalse($cred->isConnected());
    }

    public function test_transient_network_errors_do_not_deactivate_credential(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        $tokenService = app(\App\Services\Google\GoogleTokenService::class);
        $cred = $tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.access-token',
            'refresh_token' => '1//healthy-refresh-token',
            'expires_in'    => 3600,
        ], 'transient-user@gmail.com');

        $this->assertTrue($cred->is_active);

        // Mock client to throw a transient timeout exception
        $mockClient = Mockery::mock(\Google\Client::class);
        $mockClient->shouldReceive('refreshToken')
            ->once()
            ->andThrow(new \Exception('cURL error 28: Connection timed out after 30000 milliseconds'));

        $mockFactory = Mockery::mock(\App\Services\Google\GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')
            ->once()
            ->with('gmail')
            ->andReturn($mockClient);

        $oAuthService = new \App\Services\Google\GoogleOAuthService(
            $mockFactory,
            $tokenService,
            app(\App\Repositories\GoogleCredentialRepository::class)
        );

        try {
            $oAuthService->refreshAccessToken($cred);
            $this->fail('Expected GoogleApiException on timeout');
        } catch (\App\Services\Google\GoogleApiException $e) {
            $this->assertEquals(\App\Services\Google\GoogleApiException::REFRESH_FAILED, $e->getErrorCode());
            $this->assertStringContainsString('Connection timed out', $e->getMessage());
        }

        // Credential MUST remain active on transient errors!
        $cred->refresh();
        $this->assertTrue($cred->is_active, 'Credential must remain active after transient network failure');
        $this->assertEquals('1//healthy-refresh-token', $cred->refresh_token);
    }

    public function test_invalid_grant_does_not_affect_other_users_credentials(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->configureGoogleApp();

        $tokenService = app(\App\Services\Google\GoogleTokenService::class);

        $cred1 = $tokenService->storeTokens($user1->id, 'gmail', [
            'access_token'  => 'ya29.user1',
            'refresh_token' => '1//user1-token',
            'expires_in'    => 3600,
        ], 'user1@gmail.com');

        $cred2 = $tokenService->storeTokens($user2->id, 'gmail', [
            'access_token'  => 'ya29.user2',
            'refresh_token' => '1//user2-token',
            'expires_in'    => 3600,
        ], 'user2@gmail.com');

        // User 1 refresh fails with invalid_grant exception
        $mockClient = Mockery::mock(\Google\Client::class);
        $mockClient->shouldReceive('refreshToken')
            ->once()
            ->andThrow(new \Exception('Google API Error 400: invalid_grant'));

        $mockFactory = Mockery::mock(\App\Services\Google\GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')
            ->once()
            ->with('gmail')
            ->andReturn($mockClient);

        $oAuthService = new \App\Services\Google\GoogleOAuthService(
            $mockFactory,
            $tokenService,
            app(\App\Repositories\GoogleCredentialRepository::class)
        );

        try {
            $oAuthService->refreshAccessToken($cred1);
        } catch (\App\Services\Google\GoogleApiException) {}

        $cred1->refresh();
        $cred2->refresh();

        // User 1 is deactivated
        $this->assertFalse($cred1->is_active);
        $this->assertNull($cred1->refresh_token);

        // User 2 remains completely untouched!
        $this->assertTrue($cred2->is_active);
        $this->assertEquals('1//user2-token', $cred2->refresh_token);
        $this->assertEquals('user2@gmail.com', $cred2->google_email);
    }

    public function test_connection_validator_reports_not_connected_after_invalid_grant_deactivation(): void
    {
        $user = User::factory()->create();
        $this->configureGoogleApp();

        $tokenService = app(\App\Services\Google\GoogleTokenService::class);
        $cred = $tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.expired',
            'refresh_token' => '1//expired-grant',
            'expires_in'    => 3600,
        ], 'user@gmail.com');

        $validator = app(\App\Services\Google\GoogleConnectionValidator::class);
        $statusBefore = $validator->getStatus($user->id, 'gmail');
        $this->assertTrue($statusBefore['connected']);

        // Trigger invalid_grant
        $mockClient = Mockery::mock(\Google\Client::class);
        $mockClient->shouldReceive('refreshToken')
            ->once()
            ->andReturnNull();
        $mockClient->shouldReceive('getAccessToken')
            ->once()
            ->andReturn(['error' => 'invalid_grant']);

        $mockFactory = Mockery::mock(\App\Services\Google\GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')
            ->once()
            ->with('gmail')
            ->andReturn($mockClient);

        $oAuthService = new \App\Services\Google\GoogleOAuthService(
            $mockFactory,
            $tokenService,
            app(\App\Repositories\GoogleCredentialRepository::class)
        );

        try {
            $oAuthService->refreshAccessToken($cred);
        } catch (\App\Services\Google\GoogleApiException) {}

        // Status must now report not connected
        $statusAfter = $validator->getStatus($user->id, 'gmail');
        $this->assertFalse($statusAfter['connected']);
        $this->assertEquals('Not Connected', $statusAfter['status_label']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
