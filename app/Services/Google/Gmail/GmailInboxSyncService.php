<?php

namespace App\Services\Google\Gmail;

use App\Models\InboxMessage;
use App\Models\SyncJob;
use Illuminate\Support\Facades\Log;

/**
 * Service to synchronize Gmail messages into the local InboxMessage cache.
 *
 * Only finance emails (bank notifications, payment receipts) are persisted.
 * Non-finance emails are skipped silently — no InboxMessage record is created.
 */
class GmailInboxSyncService
{
    public function __construct(
        private readonly GmailService $gmailService,
        private readonly GmailFinanceFilter $financeFilter,
    ) {}

    /**
     * Synchronize Gmail messages matching query into the local inbox.
     *
     * @param  string    $query
     * @param  int       $maxResults
     * @param  int|null  $userId
     * @return array     Statistics of the sync job
     */
    public function sync(string $query, int $maxResults = 100, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) {
            throw new \Exception('User not authenticated.');
        }

        $startedAt = now();
        $startTime = microtime(true);

        // 1. Create a RUNNING SyncJob record
        $syncJob = SyncJob::create([
            'connector'   => 'gmail',
            'query'       => $query,
            'started_at'  => $startedAt,
            'status'      => 'RUNNING',
            'total_found' => 0,
            'downloaded'  => 0,
            'skipped'     => 0,
            'failed'      => 0,
        ]);

        $downloaded = 0;
        $skipped = 0;
        $failed = 0;
        $totalFound = 0;

        try {
            // 2. Fetch message stubs
            $stubs = $this->gmailService->listMessageStubs($query, $maxResults);
            $totalFound = count($stubs);
            $syncJob->update(['total_found' => $totalFound]);

            // 3. Process each message stub
            foreach ($stubs as $stub) {
                $messageId = $stub['id'];
                $threadId  = $stub['threadId'] ?? null;

                try {
                    // Check duplicate by google_message_id first (cheapest check)
                    $exists = InboxMessage::where('user_id', $userId)
                        ->where('google_message_id', $messageId)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    // Download details
                    $message = $this->gmailService->getMessage($messageId);

                    // Extract Headers for finance filter check
                    $headers = $message['payload']['headers'] ?? [];
                    $subject = null;
                    $from = null;
                    $to = null;
                    foreach ($headers as $h) {
                        $name = strtolower($h['name'] ?? '');
                        if ($name === 'subject') {
                            $subject = $h['value'] ?? null;
                        } elseif ($name === 'from') {
                            $from = $h['value'] ?? null;
                        } elseif ($name === 'to') {
                            $to = $h['value'] ?? null;
                        }
                    }

                    // ── Second-pass finance filter ────────────────────────────
                    // The Gmail query already restricts results, but this provides
                    // a hard guarantee: non-finance emails are never stored.
                    if (!$this->financeFilter->isFinanceEmail($from ?? '', $subject ?? '')) {
                        $skipped++;
                        Log::debug("GmailInboxSyncService: skipped non-finance email [{$messageId}] from [{$from}]");
                        continue;
                    }

                    // Compute checksum on raw payload (Revision 4)
                    $normalized = InboxMessage::normalizePayload($message);
                    $checksum = hash('sha256', json_encode($normalized));

                    // Extract HTML and Plain Text
                    $html = $this->gmailService->extractHtml($message);
                    $plainText = $this->gmailService->extractPlainText($message);

                    // Save locally (Revision 2 & 5)
                    InboxMessage::create([
                        'user_id'           => $userId,
                        'sync_job_id'       => $syncJob->id,
                        'connector'         => 'gmail',
                        'provider'          => null, // Nullable during sync
                        'google_message_id' => $messageId,
                        'google_thread_id'  => $threadId,
                        'history_id'        => $message['historyId'] ?? null,
                        'internal_date'     => $message['internalDate'] ?? null,
                        'subject'           => $subject,
                        'from'              => $from,
                        'to'                => $to,
                        'snippet'           => $message['snippet'] ?? null,
                        'html_body'         => $html,
                        'plain_body'        => $plainText,
                        'raw_payload'       => $normalized,
                        'labels'            => $message['labelIds'] ?? [],
                        'checksum'          => $checksum,
                        'status'            => InboxMessage::STATUS_NEW,
                    ]);

                    $downloaded++;
                } catch (\Exception $msgEx) {
                    $failed++;
                    Log::error("Failed to sync Gmail message ID {$messageId}", [
                        'exception' => $msgEx->getMessage(),
                    ]);
                }
            }

            $status = 'SUCCESS';
        } catch (\Exception $e) {
            $status = 'FAILED';
            Log::error("Gmail Inbox Synchronization failed completely", [
                'exception' => $e->getMessage(),
            ]);

            $finishedAt = now();
            $durationMs = (int) ( (microtime(true) - $startTime) * 1000 );

            $syncJob->update([
                'finished_at' => $finishedAt,
                'downloaded'  => $downloaded,
                'skipped'     => $skipped,
                'failed'      => $failed,
                'status'      => $status,
                'duration_ms' => $durationMs,
            ]);

            throw $e;
        }

        // 4. Finalize SyncJob statistics
        $finishedAt = now();
        $durationMs = (int) ( (microtime(true) - $startTime) * 1000 );

        $syncJob->update([
            'finished_at' => $finishedAt,
            'downloaded'  => $downloaded,
            'skipped'     => $skipped,
            'failed'      => $failed,
            'status'      => $status,
            'duration_ms' => $durationMs,
        ]);

        return [
            'id'          => $syncJob->id,
            'connector'   => 'gmail',
            'total_found' => $totalFound,
            'downloaded'  => $downloaded,
            'skipped'     => $skipped,
            'failed'      => $failed,
            'status'      => $status,
            'duration_ms' => $durationMs,
        ];
    }
}
