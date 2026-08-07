<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Models\InboxMessage;
use App\Models\SyncJob;
use App\Repositories\GoogleCredentialRepository;
use App\Services\Google\GoogleConnectionValidator;
use App\Services\Google\Gmail\GmailFinanceFilter;
use App\Services\Google\Gmail\GmailInboxSyncService;
use App\Services\Finance\TransactionDescriptionBuilder;
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
        private readonly FinanceImporter $importer,
        private readonly TransactionDescriptionBuilder $descriptionBuilder
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

        $reviewMessages = $messages->getCollection()->map(function (InboxMessage $message) use ($userId) {
            return $this->buildReviewMessage($message, $userId);
        });
        $messages->setCollection($reviewMessages);

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
        // Resolve the actual Gmail query at the sync entry point.
        // If the user has not provided a from: clause, fall back to the finance-safe default.
        $financeFilter = app(GmailFinanceFilter::class);
        $defaultQuery  = $financeFilter->buildDefaultQuery('');
        $rawQuery      = $request->input('query');
        $query         = $this->resolveGmailQuery($rawQuery, $defaultQuery);
        $maxResults    = (int) $request->input('max_results', 100);
        $userId        = auth()->id();

        // Instrumentation: log the exact query that will be sent to the Gmail API.
        Log::info('FINAL GMAIL QUERY: ' . $query);

        $validator  = app(GoogleConnectionValidator::class);
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
     * Resolve the Gmail query sent to the API.
     *
     * Server owns the default. A query is treated as "user-provided" only if it
     * explicitly contains a from: operator. Any query without from: (including the
     * UI placeholder 'newer_than:30d') falls back to the finance whitelist default.
     */
    private function resolveGmailQuery(?string $rawQuery, string $defaultQuery): string
    {
        $normalizedQuery = trim((string) $rawQuery);

        // If no query, or no from: clause is present, the server default takes over.
        if ($normalizedQuery === '' || !str_contains($normalizedQuery, 'from:')) {
            return $defaultQuery;
        }

        return $normalizedQuery;
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

        $skippedCount = count($messageIds) - $messages->count();
        \App\Services\Finance\Diagnostics\ImportDiagnostics::start();
        if ($skippedCount > 0) {
            for ($i = 0; $i < $skippedCount; $i++) {
                \App\Services\Finance\Diagnostics\ImportDiagnostics::increment('skipped');
            }
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

        $report = \App\Services\Finance\Diagnostics\ImportDiagnostics::stop();

        $successMessage = sprintf(
            'Import complete. Imported: %d, Duplicates Ignored: %d, Failed: %d.',
            $imported,
            $duplicate,
            $failed
        );

        if (!empty($report)) {
            $successMessage .= sprintf(
                ' Diagnostics: Fallback: %d, Ambiguous: %d, Resolver Errors: %d, Merchant Learned: %d, Auto Categorized: %d. Avg Resolve: %.1fms, Avg Import: %.1fms.',
                $report['fallback'] ?? 0,
                $report['ambiguous'] ?? 0,
                $report['resolver_errors'] ?? 0,
                $report['merchant_learned'] ?? 0,
                $report['auto_categorized'] ?? 0,
                $report['avg_resolve_time_ms'] ?? 0,
                $report['avg_import_time_ms'] ?? 0
            );
        }

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

        \App\Services\Finance\Diagnostics\ImportDiagnostics::start();

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

        $report = \App\Services\Finance\Diagnostics\ImportDiagnostics::stop();

        $successMessage = sprintf(
            'Import complete. Imported: %d, Duplicates Ignored: %d, Failed: %d.',
            $imported,
            $duplicate,
            $failed
        );

        if (!empty($report)) {
            $successMessage .= sprintf(
                ' Diagnostics: Fallback: %d, Ambiguous: %d, Resolver Errors: %d, Merchant Learned: %d, Auto Categorized: %d. Avg Resolve: %.1fms, Avg Import: %.1fms.',
                $report['fallback'] ?? 0,
                $report['ambiguous'] ?? 0,
                $report['resolver_errors'] ?? 0,
                $report['merchant_learned'] ?? 0,
                $report['auto_categorized'] ?? 0,
                $report['avg_resolve_time_ms'] ?? 0,
                $report['avg_import_time_ms'] ?? 0
            );
        }

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

    private function buildReviewMessage(InboxMessage $message, int $userId): array
    {
        try {
            $result = $this->parserManager->parse($message, new \App\DTO\ParserContext($message->connector));
            $tx = $result->parsedTransaction;
        } catch (\Throwable $e) {
            $result = null;
            $tx = null;
        }

        $description = $tx ? $this->descriptionBuilder->build($tx) : null;
        $warnings = $this->buildWarnings($tx, $description);
        $confidence = $this->resolveReviewConfidence($tx);

        return [
            'model' => $message,
            'parsed' => $tx,
            'description' => $description,
            'warnings' => $warnings,
            'confidence' => $confidence,
            'status_label' => $this->statusLabel($message->status),
            'status_class' => $this->statusClass($message->status),
            'has_internal_transfer_candidate' => (bool) data_get($message->raw_payload, 'internal_transfer_candidate', false),
            'review_badge' => $this->reviewBadge($message, $tx, $warnings),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            InboxMessage::STATUS_READY => 'Ready to Import',
            InboxMessage::STATUS_IMPORTED => 'Imported',
            InboxMessage::STATUS_IGNORED => 'Ignored',
            default => 'Needs Review',
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            InboxMessage::STATUS_READY => 'bg-purple-500/10 text-purple-500 border-purple-500/20',
            InboxMessage::STATUS_IMPORTED => 'bg-green-500/10 text-green-500 border-green-500/20',
            InboxMessage::STATUS_IGNORED => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            default => 'bg-amber-500/10 text-amber-600 border-amber-500/20 ring-1 ring-amber-400/40',
        };
    }

    private function buildWarnings(?\App\DTO\ParsedTransaction $tx, ?string $description): array
    {
        if (!$tx) {
            return ['No parsed transaction available'];
        }

        $warnings = [];
        if (blank($description)) {
            $warnings[] = 'Description is empty';
        }
        if (blank($tx->merchant)) {
            $warnings[] = 'Merchant missing';
        }
        if (blank($tx->amount) || (int) $tx->amount <= 0) {
            $warnings[] = 'Amount missing';
        }
        if (!$tx->transactionDate) {
            $warnings[] = 'Transaction date missing';
        }

        return $warnings;
    }

    private function resolveReviewConfidence(?\App\DTO\ParsedTransaction $tx): string
    {
        if (!$tx || !$tx->amount || !$tx->transactionDate) {
            return 'LOW';
        }

        if (blank($tx->merchant) || blank($tx->counterparty)) {
            return 'MEDIUM';
        }

        return 'HIGH';
    }

    private function reviewBadge(InboxMessage $message, ?\App\DTO\ParsedTransaction $tx, array $warnings): string
    {
        if (($message->raw_payload['internal_transfer_candidate'] ?? false) === true) {
            return 'Internal Transfer Candidate';
        }

        if (!$tx || !empty($warnings)) {
            return 'Needs Review';
        }

        return 'Ready';
    }
}
