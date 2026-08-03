<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Models\InboxMessage;
use App\Models\SyncJob;
use App\Repositories\GoogleCredentialRepository;
use App\Services\Google\GoogleConnectionValidator;
use App\Services\Google\Gmail\GmailInboxSyncService;
use App\Services\Parsers\ParserManager;
use App\Services\Finance\FinanceImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * Controller managing the local Gmail inbox synchronization, parser execution, and ledger imports.
 */
class GmailInboxController extends Controller
{
    public function __construct(
        private readonly GoogleCredentialRepository $credentialRepo,
        private readonly GmailInboxSyncService $syncService,
        private readonly ParserManager $parserManager,
        private readonly FinanceImporter $importer
    ) {}

    /**
     * Display the paginated list of local inbox messages, import stats, and sync stats.
     */
    public function index(Request $request): View
    {
        $userId = auth()->id();

        // Check Google connection status with scope details
        $validator = app(GoogleConnectionValidator::class);
        $googleStatus = $validator->getStatus($userId, 'gmail');

        $isConnected = $googleStatus['connected'];
        $googleEmail = $googleStatus['google_email'];
        $hasRequiredScopes = $googleStatus['has_required_scopes'];
        $missingScopes = $googleStatus['missing_scopes'];

        // Fetch last sync job stats
        $lastSyncJob = SyncJob::where('connector', 'gmail')
            ->whereIn('status', ['SUCCESS', 'FAILED'])
            ->orderBy('id', 'desc')
            ->first();

        // Calculate Import Statistics (Phase 6)
        $stats = [
            'ready'     => InboxMessage::where('user_id', $userId)->where('status', InboxMessage::STATUS_READY)->count(),
            'imported'  => InboxMessage::where('user_id', $userId)->where('status', InboxMessage::STATUS_IMPORTED)->where(function($q) {
                               $q->whereNull('raw_payload->is_duplicate')->orWhere('raw_payload->is_duplicate', false);
                           })->count(),
            'duplicate' => InboxMessage::where('user_id', $userId)->where('status', InboxMessage::STATUS_IMPORTED)->where('raw_payload->is_duplicate', true)->count(),
            'failed'    => InboxMessage::where('user_id', $userId)->where('status', InboxMessage::STATUS_FAILED)->count(),
        ];

        // Paginated local inbox search
        $search = $request->input('search');
        $messages = InboxMessage::where('user_id', $userId)
            ->search($search)
            ->orderBy('internal_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('settings.google.gmail_inbox', compact(
            'messages',
            'isConnected',
            'googleEmail',
            'lastSyncJob',
            'search',
            'stats',
            'hasRequiredScopes',
            'missingScopes'
        ));
    }

    /**
     * Handle the Gmail inbox synchronization request and auto-trigger parser processing.
     */
    public function sync(Request $request): RedirectResponse
    {
        $query = $request->input('query', 'newer_than:30d');
        $maxResults = (int) $request->input('max_results', 100);
        $userId = auth()->id();

        $validator = app(GoogleConnectionValidator::class);
        $credential = $this->credentialRepo->findByUserAndProvider($userId, 'gmail');

        if (!$credential || !$credential->is_active) {
            return redirect()
                ->route('gmail-inbox.index')
                ->withErrors(['google' => 'Google account not connected. Please connect your account in Settings.']);
        }

        if (!$validator->hasRequiredScopes($credential, 'gmail')) {
            return redirect()
                ->route('gmail-inbox.index')
                ->withErrors(['google' => 'ACCESS_TOKEN_SCOPE_INSUFFICIENT']);
        }

        try {
            $stats = $this->syncService->sync($query, $maxResults);

            // Auto-parse newly downloaded messages to move them to READY status
            $this->parseNewMessages($userId);

            $message = sprintf(
                'Gmail synchronization complete. Downloaded: %d, Skipped: %d, Failed: %d.',
                $stats['downloaded'],
                $stats['skipped'],
                $stats['failed']
            );

            return redirect()
                ->route('gmail-inbox.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'insufficientPermissions') || str_contains($msg, '403') || str_contains($msg, 'scope')) {
                return redirect()
                    ->route('gmail-inbox.index')
                    ->withErrors(['google' => 'ACCESS_TOKEN_SCOPE_INSUFFICIENT']);
            }
            return redirect()
                ->route('gmail-inbox.index')
                ->withErrors(['google' => 'Sync failed: ' . $msg]);
        }
    }

    /**
     * Manually trigger parser on all unparsed messages.
     */
    public function parseAll(): RedirectResponse
    {
        $userId = auth()->id();
        $counts = $this->parseNewMessages($userId);

        $msg = sprintf(
            'Parsing complete. Ready to Import: %d, Ignored: %d, Failed: %d.',
            $counts['parsed'],
            $counts['ignored'],
            $counts['failed']
        );

        return redirect()
            ->route('gmail-inbox.index')
            ->with('success', $msg);
    }

    /**
     * Import selected messages by ID.
     */
    public function importSelected(Request $request): RedirectResponse
    {
        $userId = auth()->id();
        $messageIds = $request->input('selected_messages', []);

        if (empty($messageIds)) {
            return redirect()
                ->route('gmail-inbox.index')
                ->withErrors(['import' => 'No messages selected for import.']);
        }

        $messages = InboxMessage::where('user_id', $userId)
            ->whereIn('id', $messageIds)
            ->where('status', InboxMessage::STATUS_READY)
            ->get();

        if ($messages->isEmpty()) {
            return redirect()
                ->route('gmail-inbox.index')
                ->withErrors(['import' => 'No selected messages are in READY status.']);
        }

        $imported = 0;
        $duplicate = 0;
        $failed = 0;

        foreach ($messages as $msg) {
            $result = $this->importer->import($msg);
            if ($result->status === 'SUCCESS') {
                $imported++;
            } elseif ($result->status === 'DUPLICATE') {
                $duplicate++;
            } else {
                $failed++;
            }
        }

        $successMessage = sprintf(
            'Import complete. Imported: %d, Duplicates Ignored: %d, Failed: %d.',
            $imported,
            $duplicate,
            $failed
        );

        return redirect()
            ->route('gmail-inbox.index')
            ->with('success', $successMessage);
    }

    /**
     * Import all messages with status READY.
     */
    public function importAllReady(): RedirectResponse
    {
        $userId = auth()->id();

        $messages = InboxMessage::where('user_id', $userId)
            ->where('status', InboxMessage::STATUS_READY)
            ->get();

        if ($messages->isEmpty()) {
            return redirect()
                ->route('gmail-inbox.index')
                ->with('success', 'No messages found with READY status to import.');
        }

        $imported = 0;
        $duplicate = 0;
        $failed = 0;

        foreach ($messages as $msg) {
            $result = $this->importer->import($msg);
            if ($result->status === 'SUCCESS') {
                $imported++;
            } elseif ($result->status === 'DUPLICATE') {
                $duplicate++;
            } else {
                $failed++;
            }
        }

        $successMessage = sprintf(
            'Import complete. Imported: %d, Duplicates Ignored: %d, Failed: %d.',
            $imported,
            $duplicate,
            $failed
        );

        return redirect()
            ->route('gmail-inbox.index')
            ->with('success', $successMessage);
    }

    /**
     * Show a detailed inbox message locally from SQL cache.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $message = InboxMessage::where('user_id', auth()->id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id'           => $message->google_message_id,
                    'threadId'     => $message->google_thread_id,
                    'subject'      => $message->subject,
                    'from'         => $message->from,
                    'to'           => $message->to,
                    'snippet'      => $message->snippet,
                    'html'         => $message->html_body,
                    'plainText'    => $message->plain_body,
                    'rawPayload'   => $message->raw_payload,
                    'labels'       => $message->labels,
                    'checksum'     => $message->checksum,
                    'status'       => $message->status,
                    'parsedAt'     => $message->parsed_at?->toIso8601String(),
                    'internalDate' => $message->internal_date,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Message not found locally: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Parse unparsed messages for the user.
     */
    private function parseNewMessages(int $userId): array
    {
        $newMessages = InboxMessage::where('user_id', $userId)
            ->whereIn('status', [InboxMessage::STATUS_NEW, InboxMessage::STATUS_SYNCED, InboxMessage::STATUS_PARSED])
            ->get();

        $parsedCount = 0;
        $ignoredCount = 0;
        $failedCount = 0;

        foreach ($newMessages as $msg) {
            try {
                $context = new \App\DTO\ParserContext($msg->connector);
                $result = $this->parserManager->parse($msg, $context);
                if ($result->supported) {
                    if ($result->parsedTransaction) {
                        $msg->update([
                            'status' => InboxMessage::STATUS_READY,
                            'provider' => $result->parsedMessage?->provider ?? $msg->provider,
                            'parsed_at' => now(),
                        ]);
                        $parsedCount++;
                    } else {
                        $msg->update([
                            'status' => InboxMessage::STATUS_PARSED,
                            'provider' => $result->parsedMessage?->provider ?? $msg->provider,
                            'parsed_at' => now(),
                        ]);
                        $parsedCount++;
                    }
                } else {
                    $msg->update([
                        'status' => InboxMessage::STATUS_IGNORED,
                        'parsed_at' => now(),
                    ]);
                    $ignoredCount++;
                }
            } catch (\App\Exceptions\UnsupportedMessageException $e) {
                $msg->update([
                    'status' => InboxMessage::STATUS_IGNORED,
                    'parsed_at' => now(),
                ]);
                $ignoredCount++;
            } catch (\Exception $e) {
                Log::error("Failed to parse message {$msg->id}: " . $e->getMessage());
                $msg->update([
                    'status' => InboxMessage::STATUS_FAILED,
                    'parsed_at' => now(),
                ]);
                $failedCount++;
            }
        }

        return [
            'parsed' => $parsedCount,
            'ignored' => $ignoredCount,
            'failed' => $failedCount,
        ];
    }
}
