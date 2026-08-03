<?php

namespace Tests\Feature\Google;

use App\Models\InboxMessage;
use App\Models\SyncJob;
use App\Models\User;
use App\Repositories\GoogleCredentialRepository;
use App\Services\Google\Gmail\GmailInboxSyncService;
use App\Services\Google\Gmail\GmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Tests for the Gmail Inbox Synchronization and generic Connector caching layer.
 */
class GmailInboxSyncTest extends TestCase
{
    use RefreshDatabase;

    private GoogleCredentialRepository $credentialRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentialRepo = app(GoogleCredentialRepository::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ─── Routing & Security Tests ────────────────────────────────────────────

    public function test_inbox_route_requires_authentication(): void
    {
        $response = $this->get(route('gmail-inbox.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_inbox_renders_warning_when_not_connected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('gmail-inbox.index'));

        $response->assertStatus(200);
        $response->assertSee('Google Account Disconnected');
    }

    public function test_inbox_renders_list_and_stats(): void
    {
        $user = User::factory()->create();

        // Connect user
        $this->credentialRepo->upsert([
            'user_id'       => $user->id,
            'provider'      => 'gmail',
            'google_email'  => 'cache-user@gmail.com',
            'access_token'  => 'valid',
            'refresh_token' => 'refresh',
            'expires_at'    => now()->addHour(),
            'is_active'     => true,
            'scopes'        => ['openid', 'https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/gmail.readonly'],
        ]);

        // Create a SyncJob and Cached Messages
        $syncJob = SyncJob::create([
            'connector'   => 'gmail',
            'query'       => 'newer_than:30d',
            'started_at'  => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(4),
            'total_found' => 2,
            'downloaded'  => 2,
            'skipped'     => 0,
            'failed'      => 0,
            'status'      => 'SUCCESS',
            'duration_ms' => 1200,
        ]);

        InboxMessage::create([
            'user_id'           => $user->id,
            'sync_job_id'       => $syncJob->id,
            'connector'         => 'gmail',
            'google_message_id' => 'msg-cache-1',
            'subject'           => 'Mandiri Transaction Notification',
            'from'              => 'noreply.livin@bankmandiri.co.id',
            'snippet'           => 'You have transfered 500,000 IDR',
            'status'            => 'NEW',
        ]);

        InboxMessage::create([
            'user_id'           => $user->id,
            'sync_job_id'       => $syncJob->id,
            'connector'         => 'gmail',
            'google_message_id' => 'msg-cache-2',
            'subject'           => 'BCA Bank Transfer Info',
            'from'              => 'bca@bca.co.id',
            'snippet'           => 'Transfer from Andy received',
            'status'            => 'NEW',
        ]);

        $response = $this->actingAs($user)->get(route('gmail-inbox.index'));

        $response->assertStatus(200);
        $response->assertSee('cache-user@gmail.com');
        $response->assertSee('Mandiri Transaction Notification');
        $response->assertSee('BCA Bank Transfer Info');
        // Verify last sync statistics are displayed
        $response->assertSee('1.20 seconds');
        $response->assertSee('SUCCESS');
    }

    public function test_show_message_details_retrieves_locally(): void
    {
        $user = User::factory()->create();

        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'google_message_id' => 'msg-cached-999',
            'subject'           => 'BNI Alert',
            'from'              => 'bnicall@bni.co.id',
            'snippet'           => 'Transfer alert',
            'html_body'         => '<p>Secret Content</p>',
            'plain_body'        => 'Secret Content',
            'checksum'          => 'dummy-checksum-sha256',
            'status'            => 'NEW',
            'raw_payload'       => ['dummy' => 'payload'],
        ]);

        // Expect no mock calls on GmailService, verifying it loads purely from SQL cache
        $mockGmailService = Mockery::mock(GmailService::class);
        $this->app->instance(GmailService::class, $mockGmailService);

        $response = $this->actingAs($user)->get(route('gmail-inbox.show', $message->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id'        => 'msg-cached-999',
                'subject'   => 'BNI Alert',
                'html'      => '<p>Secret Content</p>',
                'plainText' => 'Secret Content',
                'checksum'  => 'dummy-checksum-sha256',
                'status'    => 'NEW',
            ]
        ]);
    }

    // ─── Sync Execution & Caching Tests ──────────────────────────────────────

    public function test_sync_downloads_messages_and_skips_duplicates(): void
    {
        $user = User::factory()->create();

        // Connect user
        $this->credentialRepo->upsert([
            'user_id'       => $user->id,
            'provider'      => 'gmail',
            'google_email'  => 'sync-test@gmail.com',
            'access_token'  => 'valid',
            'refresh_token' => 'refresh',
            'expires_at'    => now()->addHour(),
            'is_active'     => true,
            'scopes'        => ['openid', 'https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/gmail.readonly'],
        ]);

        // Seed 1 message already cached
        InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'google_message_id' => 'existing-msg',
            'subject'           => 'Old Subject',
            'from'              => 'old@bca.co.id',
            'status'            => 'NEW',
        ]);

        // Mock GmailService to return 2 message stubs: one existing, one new
        $mockGmailService = Mockery::mock(GmailService::class);
        $mockGmailService->shouldReceive('listMessageStubs')
            ->once()
            ->with('newer_than:30d', 100)
            ->andReturn([
                ['id' => 'existing-msg', 'threadId' => 'thread-1'],
                ['id' => 'new-msg', 'threadId' => 'thread-2'],
            ]);

        // Mock getMessage for the new message ONLY
        $mockGmailService->shouldReceive('getMessage')
            ->once()
            ->with('new-msg')
            ->andReturn([
                'id' => 'new-msg',
                'threadId' => 'thread-2',
                'historyId' => '1002',
                'internalDate' => '1714900000000',
                'snippet' => 'Transfer success info',
                'payload' => [
                    'headers' => [
                        ['name' => 'Subject', 'value' => 'BCA Transfer Received'],
                        ['name' => 'From', 'value' => 'bca@bca.co.id'],
                        ['name' => 'To', 'value' => 'me@gmail.com'],
                    ]
                ]
            ]);

        $mockGmailService->shouldReceive('extractHtml')
            ->once()
            ->andReturn('<html>New HTML</html>');

        $mockGmailService->shouldReceive('extractPlainText')
            ->once()
            ->andReturn('New Plain Text');

        $this->app->instance(GmailService::class, $mockGmailService);

        // Run sync via POST route
        $response = $this->actingAs($user)->post(route('gmail-inbox.sync'), [
            'query'       => 'newer_than:30d',
            'max_results' => 100,
        ]);

        $response->assertRedirect(route('gmail-inbox.index'));
        $response->assertSessionHas('success');

        // Check SyncJob stats
        $job = SyncJob::latest('id')->first();
        $this->assertNotNull($job);
        $this->assertEquals(2, $job->total_found);
        $this->assertEquals(1, $job->downloaded);
        $this->assertEquals(1, $job->skipped);
        $this->assertEquals(0, $job->failed);
        $this->assertEquals('SUCCESS', $job->status);

        // Check new InboxMessage attributes
        $newMsg = InboxMessage::where('google_message_id', 'new-msg')->first();
        $this->assertNotNull($newMsg);

        $this->assertEquals('BCA Transfer Received', $newMsg->subject);
        $this->assertEquals('bca@bca.co.id', $newMsg->from);
        $this->assertEquals('<html>New HTML</html>', $newMsg->html_body);
        $this->assertEquals('New Plain Text', $newMsg->plain_body);
        $this->assertEquals('PARSED', $newMsg->status); // Lifecycle status: PARSED
        $this->assertEquals('BCA', $newMsg->provider); // Provider set by parser
        $this->assertNotNull($newMsg->checksum); // Checksum generated (Revision 4)
        
        $normalized = InboxMessage::normalizePayload($newMsg->raw_payload);
        $this->assertEquals(hash('sha256', json_encode($normalized)), $newMsg->checksum);
    }

    public function test_sync_blocks_request_and_redirects_if_scopes_are_insufficient(): void
    {
        $user = User::factory()->create();

        // Connect user with insufficient scopes (missing gmail.readonly)
        $this->credentialRepo->upsert([
            'user_id'       => $user->id,
            'provider'      => 'gmail',
            'google_email'  => 'sync-test@gmail.com',
            'access_token'  => 'valid',
            'refresh_token' => 'refresh',
            'expires_at'    => now()->addHour(),
            'is_active'     => true,
            'scopes'        => ['openid', 'https://www.googleapis.com/auth/userinfo.email'],
        ]);

        $response = $this->actingAs($user)->post(route('gmail-inbox.sync'), [
            'query'       => 'newer_than:30d',
            'max_results' => 100,
        ]);

        $response->assertRedirect(route('gmail-inbox.index'));
        $response->assertSessionHasErrors(['google']);
        $this->assertEquals(
            'ACCESS_TOKEN_SCOPE_INSUFFICIENT',
            session('errors')->get('google')[0]
        );
    }

    public function test_sync_handles_google_api_scope_exception_during_sync(): void
    {
        $user = User::factory()->create();

        // Connect user with sufficient scopes initially
        $this->credentialRepo->upsert([
            'user_id'       => $user->id,
            'provider'      => 'gmail',
            'google_email'  => 'sync-test@gmail.com',
            'access_token'  => 'valid',
            'refresh_token' => 'refresh',
            'expires_at'    => now()->addHour(),
            'is_active'     => true,
            'scopes'        => ['openid', 'https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/gmail.readonly'],
        ]);

        // Mock GmailService to throw a 403 insufficientPermissions exception during listMessageStubs
        $mockGmailService = Mockery::mock(GmailService::class);
        $mockGmailService->shouldReceive('listMessageStubs')
            ->once()
            ->andThrow(new \Exception('insufficientPermissions or 403 error from Google API'));

        $this->app->instance(GmailService::class, $mockGmailService);

        $response = $this->actingAs($user)->post(route('gmail-inbox.sync'), [
            'query'       => 'newer_than:30d',
            'max_results' => 100,
        ]);

        $response->assertRedirect(route('gmail-inbox.index'));
        $response->assertSessionHasErrors(['google']);
        $this->assertEquals(
            'ACCESS_TOKEN_SCOPE_INSUFFICIENT',
            session('errors')->get('google')[0]
        );

        // Check SyncJob is finalized as FAILED in database
        $job = SyncJob::latest('id')->first();
        $this->assertNotNull($job);
        $this->assertEquals('FAILED', $job->status);
    }
}
