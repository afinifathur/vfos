<?php

namespace Tests\Feature\Google;

use App\Models\User;
use App\Repositories\AppSettingRepository;
use App\Services\Google\GoogleConnectionValidator;
use App\Services\Google\GoogleTokenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests GoogleConnectionValidator status checks.
 */
class GoogleConnectionValidatorTest extends TestCase
{
    use RefreshDatabase;

    private GoogleConnectionValidator $validator;
    private GoogleTokenService $tokenService;
    private AppSettingRepository $appSettings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator    = app(GoogleConnectionValidator::class);
        $this->tokenService = app(GoogleTokenService::class);
        $this->appSettings  = app(AppSettingRepository::class);
    }

    // ─── App Configuration ────────────────────────────────────────────────────

    public function test_is_app_configured_returns_false_when_no_credentials(): void
    {
        $this->assertFalse($this->validator->isAppConfigured());
    }

    public function test_is_app_configured_returns_true_when_both_keys_present(): void
    {
        $this->appSettings->set('google_client_id', 'test-client-id', 'google');
        $this->appSettings->set('google_client_secret', 'test-client-secret', 'google');

        $this->assertTrue($this->validator->isAppConfigured());
    }

    public function test_is_app_configured_returns_false_when_only_id_present(): void
    {
        $this->appSettings->set('google_client_id', 'test-client-id', 'google');
        // No secret

        $this->assertFalse($this->validator->isAppConfigured());
    }

    // ─── Connection Status: Not Connected ────────────────────────────────────

    public function test_status_is_not_connected_for_new_user(): void
    {
        $user   = User::factory()->create();
        $status = $this->validator->getStatus($user->id, 'gmail');

        $this->assertFalse($status['connected']);
        $this->assertEquals('Not Connected', $status['status_label']);
        $this->assertEquals('slate', $status['status_color']);
        $this->assertNull($status['google_email']);
        $this->assertNull($status['last_connected_at']);
    }

    // ─── Connection Status: Connected ────────────────────────────────────────

    public function test_status_is_connected_after_token_storage(): void
    {
        $user = User::factory()->create();

        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.valid-token',
            'refresh_token' => '1//refresh-token',
            'expires_in'    => 3600,
            'scope'         => 'openid https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/gmail.readonly',
        ], 'connected@gmail.com');

        $status = $this->validator->getStatus($user->id, 'gmail');

        $this->assertTrue($status['connected']);
        $this->assertEquals('connected@gmail.com', $status['google_email']);
        $this->assertEquals('green', $status['status_color']);
        $this->assertNotNull($status['last_connected_at']);
        $this->assertTrue($status['has_refresh_token']);
        $this->assertTrue($status['has_required_scopes']);
    }

    public function test_status_is_reconnect_required_when_scopes_are_missing(): void
    {
        $user = User::factory()->create();

        // Storing tokens without the required gmail.readonly scope
        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.valid-token',
            'refresh_token' => '1//refresh-token',
            'expires_in'    => 3600,
            'scope'         => 'https://www.googleapis.com/auth/userinfo.profile',
        ], 'connected@gmail.com');

        $status = $this->validator->getStatus($user->id, 'gmail');

        $this->assertTrue($status['connected']);
        $this->assertEquals('red', $status['status_color']);
        $this->assertFalse($status['has_required_scopes']);
        $this->assertContains('https://www.googleapis.com/auth/gmail.readonly', $status['missing_scopes']);
    }

    public function test_status_is_not_connected_after_disconnect(): void
    {
        $user = User::factory()->create();
        $repo = app(\App\Repositories\GoogleCredentialRepository::class);

        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.token',
            'refresh_token' => '1//refresh',
            'expires_in'    => 3600,
        ]);

        $repo->deactivate($user->id, 'gmail');

        $status = $this->validator->getStatus($user->id, 'gmail');

        $this->assertFalse($status['connected']);
    }

    // ─── Settings Page: Save Config ───────────────────────────────────────────

    public function test_save_google_config_stores_client_id_plaintext(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.google-config'), [
            'google_client_id'     => 'my-client-id.apps.googleusercontent.com',
            'google_client_secret' => 'GOCSPX-mysecret',
        ]);

        $storedId = $this->appSettings->get('google_client_id');
        $this->assertEquals('my-client-id.apps.googleusercontent.com', $storedId);
    }

    public function test_save_google_config_stores_secret_encrypted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.google-config'), [
            'google_client_id'     => 'my-client-id.apps.googleusercontent.com',
            'google_client_secret' => 'GOCSPX-supersecret',
        ]);

        // Raw DB value must NOT be plaintext
        $rawSecret = \DB::table('app_settings')
            ->where('key', 'google_client_secret')
            ->value('value');
        $this->assertNotEquals('GOCSPX-supersecret', $rawSecret);

        // But repository must return the decrypted value
        $decrypted = $this->appSettings->get('google_client_secret');
        $this->assertEquals('GOCSPX-supersecret', $decrypted);
    }

    public function test_save_google_config_keeps_existing_secret_when_blank(): void
    {
        $user = User::factory()->create();

        // Store initial secret directly via repository (bypasses HTTP/validation)
        $this->appSettings->set('google_client_id', 'client-id', 'google');
        $this->appSettings->set('google_client_secret', 'GOCSPX-original', 'google');

        // HTTP request with blank secret — should NOT overwrite the stored secret
        $this->actingAs($user)->post(route('settings.google-config'), [
            'google_client_id'     => 'client-id-updated',
            'google_client_secret' => '',
        ]);

        // Secret should remain unchanged
        $this->assertEquals('GOCSPX-original', $this->appSettings->get('google_client_secret'));
        // But client_id should be updated
        $this->assertEquals('client-id-updated', $this->appSettings->get('google_client_id'));
    }

    // ─── Multi-Provider Status ────────────────────────────────────────────────

    public function test_get_status_for_all_returns_all_providers(): void
    {
        $user     = User::factory()->create();
        $statuses = $this->validator->getStatusForAll($user->id);

        $this->assertArrayHasKey('gmail', $statuses);
        $this->assertArrayHasKey('drive', $statuses);
        $this->assertArrayHasKey('calendar', $statuses);

        foreach ($statuses as $status) {
            $this->assertArrayHasKey('connected', $status);
            $this->assertArrayHasKey('status_label', $status);
            $this->assertArrayHasKey('app_configured', $status);
        }
    }
}
