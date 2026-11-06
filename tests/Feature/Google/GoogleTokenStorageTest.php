<?php

namespace Tests\Feature\Google;

use App\Models\GoogleCredential;
use App\Models\User;
use App\Repositories\GoogleCredentialRepository;
use App\Services\Google\GoogleTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests token persistence and encryption against the database.
 * Pure model helper tests are in Tests\Unit\Google\GoogleCredentialModelTest.
 */
class GoogleTokenStorageTest extends TestCase
{
    use RefreshDatabase;

    private GoogleTokenService $tokenService;
    private GoogleCredentialRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(GoogleTokenService::class);
        $this->repo         = app(GoogleCredentialRepository::class);
    }

    public function test_stores_tokens_and_creates_credential_record(): void
    {
        $user = User::factory()->create();

        $credential = $this->tokenService->storeTokens(
            userId:      $user->id,
            provider:    'gmail',
            tokenData:   [
                'access_token'  => 'ya29.fake-access-token',
                'refresh_token' => '1//fake-refresh-token',
                'expires_in'    => 3600,
                'scope'         => 'openid https://www.googleapis.com/auth/gmail.readonly',
            ],
            googleEmail: 'testuser@gmail.com'
        );

        $this->assertInstanceOf(GoogleCredential::class, $credential);
        $this->assertEquals($user->id, $credential->user_id);
        $this->assertEquals('gmail', $credential->provider);
        $this->assertEquals('testuser@gmail.com', $credential->google_email);
        $this->assertTrue($credential->is_active);
        $this->assertNotNull($credential->last_connected_at);
    }

    public function test_refresh_token_is_stored_encrypted_in_database(): void
    {
        $user = User::factory()->create();

        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.access',
            'refresh_token' => '1//super-secret-refresh-token',
            'expires_in'    => 3600,
        ]);

        $rawValue = \DB::table('google_credentials')
            ->where('user_id', $user->id)
            ->value('refresh_token');

        $this->assertNotEquals('1//super-secret-refresh-token', $rawValue);
        $this->assertNotEmpty($rawValue);

        $credential = GoogleCredential::where('user_id', $user->id)->first();
        $this->assertEquals('1//super-secret-refresh-token', $credential->refresh_token);
    }

    public function test_access_token_is_stored_encrypted_in_database(): void
    {
        $user = User::factory()->create();

        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.plaintext-access-token',
            'refresh_token' => '1//refresh',
            'expires_in'    => 3600,
        ]);

        $rawValue = \DB::table('google_credentials')
            ->where('user_id', $user->id)
            ->value('access_token');

        $this->assertNotEquals('ya29.plaintext-access-token', $rawValue);

        $credential = GoogleCredential::where('user_id', $user->id)->first();
        $this->assertEquals('ya29.plaintext-access-token', $credential->access_token);
    }

    public function test_scopes_are_parsed_from_space_separated_string(): void
    {
        $user = User::factory()->create();

        $credential = $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.access',
            'refresh_token' => '1//refresh',
            'expires_in'    => 3600,
            'scope'         => 'openid https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/gmail.readonly',
        ]);

        $this->assertIsArray($credential->scopes);
        $this->assertCount(3, $credential->scopes);
        $this->assertContains('openid', $credential->scopes);
    }

    public function test_upsert_overwrites_existing_credential_for_same_user_provider(): void
    {
        $user = User::factory()->create();

        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token' => 'ya29.first', 'refresh_token' => '1//first', 'expires_in' => 3600,
        ], 'first@gmail.com');

        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token' => 'ya29.second', 'refresh_token' => '1//second', 'expires_in' => 3600,
        ], 'second@gmail.com');

        $this->assertEquals(1, GoogleCredential::where('user_id', $user->id)->count());

        $credential = GoogleCredential::where('user_id', $user->id)->first();
        $this->assertEquals('second@gmail.com', $credential->google_email);
    }

    public function test_deactivate_clears_tokens_and_marks_inactive(): void
    {
        $user = User::factory()->create();

        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token' => 'ya29.access', 'refresh_token' => '1//refresh', 'expires_in' => 3600,
        ]);

        $this->repo->deactivate($user->id, 'gmail');

        $credential = GoogleCredential::where('user_id', $user->id)->first();
        $this->assertFalse($credential->is_active);
        $this->assertNull($credential->refresh_token);
        $this->assertNull($credential->access_token);
    }

    public function test_existing_refresh_token_survives_callback_without_refresh_token(): void
    {
        $user = User::factory()->create();

        // 1. Initial connection with valid refresh token
        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.initial-access',
            'refresh_token' => '1//existing-valid-refresh-token',
            'expires_in'    => 3600,
        ], 'afin@gmail.com');

        $initialCred = GoogleCredential::where('user_id', $user->id)->first();
        $this->assertEquals('1//existing-valid-refresh-token', $initialCred->refresh_token);
        $this->assertTrue($initialCred->is_active);

        // 2. Re-authorization where Google omits refresh_token
        $reconnectedCred = $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token' => 'ya29.new-access-token',
            'expires_in'   => 3600,
            // refresh_token omitted
        ], 'afin@gmail.com');

        $this->assertEquals('1//existing-valid-refresh-token', $reconnectedCred->refresh_token);
        $this->assertEquals('ya29.new-access-token', $reconnectedCred->access_token);
        $this->assertTrue($reconnectedCred->is_active);
        $this->assertTrue($reconnectedCred->hasRefreshToken());
    }

    public function test_new_refresh_token_replaces_old_token(): void
    {
        $user = User::factory()->create();

        // 1. Initial
        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.old-access',
            'refresh_token' => '1//old-refresh-token',
            'expires_in'    => 3600,
        ], 'afin@gmail.com');

        // 2. Re-authorization with brand new refresh token
        $updatedCred = $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.new-access',
            'refresh_token' => '1//new-brand-refresh-token',
            'expires_in'    => 3600,
        ], 'afin@gmail.com');

        $this->assertEquals('1//new-brand-refresh-token', $updatedCred->refresh_token);
        $this->assertEquals('ya29.new-access', $updatedCred->access_token);
        $this->assertTrue($updatedCred->is_active);
    }

    public function test_first_authorization_without_refresh_token_is_handled_safely(): void
    {
        $user = User::factory()->create();

        // First-time authorization where Google omits refresh_token
        $cred = $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token' => 'ya29.first-no-refresh',
            'expires_in'   => 3600,
        ], 'afin@gmail.com');

        $this->assertNull($cred->refresh_token);
        $this->assertFalse($cred->is_active, 'Connection must not be active without a usable refresh token');
        $this->assertFalse($cred->hasRefreshToken());
        $this->assertEquals('Disconnected', $cred->statusLabel());
    }

    public function test_account_email_change_does_not_inherit_previous_user_refresh_token(): void
    {
        $user = User::factory()->create();

        // Initial connection with userA@gmail.com
        $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token'  => 'ya29.userA-access',
            'refresh_token' => '1//userA-refresh-token',
            'expires_in'    => 3600,
        ], 'userA@gmail.com');

        // Reconnect with userB@gmail.com where Google omits refresh_token
        $credB = $this->tokenService->storeTokens($user->id, 'gmail', [
            'access_token' => 'ya29.userB-access',
            'expires_in'   => 3600,
        ], 'userB@gmail.com');

        $this->assertEquals('userB@gmail.com', $credB->google_email);
        $this->assertNull($credB->refresh_token, 'Must not carry over userA refresh token to userB');
        $this->assertFalse($credB->is_active);
    }
}
