<?php

namespace Tests\Feature\Google;

use App\Models\GoogleCredential;
use App\Models\User;
use App\Repositories\GoogleCredentialRepository;
use App\Services\Google\Gmail\GmailService;
use App\Services\Google\GoogleClientFactory;
use App\Services\Google\GoogleOAuthService;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\ListMessagesResponse;
use Google\Service\Gmail\Message as GmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Tests for GmailService retrieval and HTML/text extraction.
 */
class GmailServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoogleCredentialRepository $credentialRepo;
    private GoogleClientFactory $clientFactory;
    private GoogleOAuthService $oauthService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialRepo = app(GoogleCredentialRepository::class);
        $this->clientFactory  = app(GoogleClientFactory::class);
        $this->oauthService   = app(GoogleOAuthService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ─── Token Auto-Refresh Test ─────────────────────────────────────────────

    public function test_gmail_service_refreshes_token_if_expired(): void
    {
        $user = User::factory()->create();
        
        // Store an expired credential
        $credential = $this->credentialRepo->upsert([
            'user_id'       => $user->id,
            'provider'      => 'gmail',
            'google_email'  => 'test@gmail.com',
            'access_token'  => 'expired-access-token',
            'refresh_token' => 'valid-refresh-token',
            'expires_at'    => now()->subMinutes(10), // Expired
            'is_active'     => true,
            'scopes'        => ['https://www.googleapis.com/auth/gmail.readonly'],
        ]);

        // Mock OAuthService refresh method
        $mockOauth = Mockery::mock(GoogleOAuthService::class);
        $mockOauth->shouldReceive('refreshAccessToken')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $credential->id))
            ->andReturnUsing(function ($cred) {
                $cred->access_token = 'fresh-access-token';
                $cred->expires_at = now()->addHour();
                $cred->save();
                return $cred;
            });

        // Mock ClientFactory to return a dummy client when makeWithToken is called
        $mockClient = Mockery::mock(Client::class);

        $mockFactory = Mockery::mock(GoogleClientFactory::class);
        $mockFactory->shouldReceive('makeWithToken')
            ->once()
            ->with('gmail', Mockery::on(function ($token) {
                return $token['access_token'] === 'fresh-access-token';
            }))
            ->andReturn($mockClient);

        // Bind the mocked factory and oauth service
        $service = new class($mockFactory, $mockOauth, $this->credentialRepo) extends GmailService {
            // Expose getClient for testing
            public function testGetClient(?int $userId = null) {
                $method = new \ReflectionMethod(parent::class, 'getClient');
                $method->setAccessible(true);
                return $method->invoke($this, $userId);
            }
        };

        $this->actingAs($user);
        $client = $service->testGetClient($user->id);

        $this->assertInstanceOf(Client::class, $client);
    }

    // ─── Extract HTML & Plain Text Tests ─────────────────────────────────────

    public function test_extract_html_navigates_nested_mime_payload(): void
    {
        $service = app(GmailService::class);

        // Nested MIME structure: multipart/mixed -> multipart/alternative -> text/html & text/plain
        $message = [
            'payload' => [
                'mimeType' => 'multipart/mixed',
                'parts' => [
                    [
                        'mimeType' => 'multipart/alternative',
                        'parts' => [
                            [
                                'mimeType' => 'text/plain',
                                'body' => [
                                    'data' => base64_encode('Hello world plain text')
                                ]
                            ],
                            [
                                'mimeType' => 'text/html',
                                'body' => [
                                    // Use base64url standard padding or replacement
                                    'data' => str_replace(['+', '/'], ['-', '_'], base64_encode('<p>Hello HTML</p>'))
                                ]
                            ]
                        ]
                    ],
                    [
                        'mimeType' => 'application/pdf',
                        'filename' => 'attachment.pdf',
                        'body' => ['data' => '']
                    ]
                ]
            ]
        ];

        $html = $service->extractHtml($message);
        $text = $service->extractPlainText($message);

        $this->assertEquals('<p>Hello HTML</p>', $html);
        $this->assertEquals('Hello world plain text', $text);
    }

    public function test_extract_html_returns_null_if_no_html_part_exists(): void
    {
        $service = app(GmailService::class);

        $message = [
            'payload' => [
                'mimeType' => 'multipart/alternative',
                'parts' => [
                    [
                        'mimeType' => 'text/plain',
                        'body' => [
                            'data' => base64_encode('Only plain text')
                        ]
                    ]
                ]
            ]
        ];

        $html = $service->extractHtml($message);
        $this->assertNull($html);
    }

    // ─── Controller & Routing Tests ──────────────────────────────────────────

    public function test_explorer_route_requires_authentication(): void
    {
        $response = $this->get(route('settings.google.gmail-explorer'));
        $response->assertRedirect(route('login'));
    }

    public function test_explorer_redirects_to_settings_with_error_when_not_connected(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('settings.google.gmail-explorer'));

        $response->assertRedirect(route('settings.index') . '#google');
        $response->assertSessionHasErrors('google');
    }

    public function test_explorer_renders_when_connected(): void
    {
        $user = User::factory()->create();

        // Save connected credential
        $this->credentialRepo->upsert([
            'user_id'       => $user->id,
            'provider'      => 'gmail',
            'google_email'  => 'connected@gmail.com',
            'access_token'  => 'valid-token',
            'refresh_token' => 'refresh-token',
            'expires_at'    => now()->addHour(),
            'is_active'     => true,
        ]);

        $response = $this->actingAs($user)->get(route('settings.google.gmail-explorer'));

        $response->assertStatus(200);
        $response->assertViewIs('settings.google.gmail_explorer');
        $response->assertSee('Gmail Message Explorer');
        $response->assertSee('connected@gmail.com');
    }

    public function test_show_message_details_returns_json(): void
    {
        $user = User::factory()->create();

        // Connect user
        $this->credentialRepo->upsert([
            'user_id'       => $user->id,
            'provider'      => 'gmail',
            'google_email'  => 'connected@gmail.com',
            'access_token'  => 'valid-token',
            'refresh_token' => 'refresh-token',
            'expires_at'    => now()->addHour(),
            'is_active'     => true,
        ]);

        // Mock GmailService
        $mockGmailService = Mockery::mock(GmailService::class);
        $mockGmailService->shouldReceive('getMessage')
            ->once()
            ->with('msg-123')
            ->andReturn([
                'id' => 'msg-123',
                'threadId' => 'thread-456',
                'internalDate' => '1714900000000',
                'historyId' => '999',
                'labelIds' => ['INBOX'],
                'snippet' => 'Hello from BCA',
                'payload' => [
                    'headers' => [
                        ['name' => 'Subject', 'value' => 'Notification BCA'],
                        ['name' => 'From', 'value' => 'bca@bca.co.id'],
                        ['name' => 'To', 'value' => 'me@gmail.com'],
                        ['name' => 'Date', 'value' => 'Sun, 2 Aug 2026 12:00:00 +0700'],
                    ]
                ]
            ]);

        $mockGmailService->shouldReceive('extractHtml')
            ->once()
            ->andReturn('<p>Hello HTML</p>');

        $mockGmailService->shouldReceive('extractPlainText')
            ->once()
            ->andReturn('Hello Plain Text');

        // Bind mock GmailService into the app container
        $this->app->instance(GmailService::class, $mockGmailService);

        $response = $this->actingAs($user)->get(route('settings.google.gmail-explorer.message', 'msg-123'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => 'msg-123',
                'threadId' => 'thread-456',
                'subject' => 'Notification BCA',
                'from' => 'bca@bca.co.id',
                'html' => '<p>Hello HTML</p>',
                'plainText' => 'Hello Plain Text',
            ]
        ]);
    }
}
