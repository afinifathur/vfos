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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
