<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Services\Google\Gmail\GmailService;
use App\Services\Google\GoogleConnectionValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller to search and inspect Gmail messages for connected accounts.
 *
 * All operations are read-only and no transactions are created or updated.
 */
class GmailExplorerController extends Controller
{
    public function __construct(
        private readonly GmailService $gmailService,
        private readonly GoogleConnectionValidator $validator
    ) {}

    /**
     * Display the Gmail Explorer page, and optionally perform search queries.
     */
    public function index(Request $request)
    {
        $status = $this->validator->getStatus(auth()->id(), 'gmail');
        if (!$status['connected']) {
            return redirect()
                ->to(route('settings.index') . '#google')
                ->withErrors(['google' => 'Please connect your Google account before using the Gmail Explorer.']);
        }

        $query = $request->get('q');
        $maxResults = $request->get('max_results', 20);

        $messages = [];
        $error = null;

        if (filled($query)) {
            try {
                $rawMessages = $this->gmailService->listMessages($query, (int) $maxResults);
                foreach ($rawMessages as $msg) {
                    $headers = [];
                    foreach ($msg['payload']['headers'] ?? [] as $header) {
                        $headers[strtolower($header['name'])] = $header['value'];
                    }

                    $messages[] = [
                        'id' => $msg['id'],
                        'threadId' => $msg['threadId'],
                        'internalDate' => $msg['internalDate'],
                        'historyId' => $msg['historyId'] ?? '',
                        'labelIds' => $msg['labelIds'] ?? [],
                        'snippet' => $msg['snippet'] ?? '',
                        'from' => $headers['from'] ?? '(Unknown Sender)',
                        'subject' => $headers['subject'] ?? '(No Subject)',
                        'date' => $headers['date'] ?? '(Unknown Date)',
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Gmail Explorer search failed', [
                    'user_id' => auth()->id(),
                    'error'   => $e->getMessage(),
                ]);
                $error = 'Gmail Search failed: ' . $e->getMessage();
            }
        }

        return view('settings.google.gmail_explorer', compact('messages', 'query', 'maxResults', 'error', 'status'));
    }

    /**
     * Retrieve a single Gmail message's details as JSON for AJAX requests.
     */
    public function show(string $id)
    {
        try {
            $message = $this->gmailService->getMessage($id);
            
            // Extract headers for quick reference
            $headers = [];
            foreach ($message['payload']['headers'] ?? [] as $header) {
                $headers[strtolower($header['name'])] = $header['value'];
            }

            $extractedHtml = $this->gmailService->extractHtml($message);
            $extractedPlainText = $this->gmailService->extractPlainText($message);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $message['id'],
                    'threadId' => $message['threadId'],
                    'internalDate' => $message['internalDate'],
                    'historyId' => $message['historyId'],
                    'labelIds' => $message['labelIds'] ?? [],
                    'snippet' => $message['snippet'] ?? '',
                    'subject' => $headers['subject'] ?? '(No Subject)',
                    'from' => $headers['from'] ?? '(Unknown Sender)',
                    'to' => $headers['to'] ?? '(Unknown Recipient)',
                    'date' => $headers['date'] ?? '(Unknown Date)',
                    'html' => $extractedHtml,
                    'plainText' => $extractedPlainText,
                    'rawMime' => $message['payload'] ?? null, // Raw MIME payload
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Gmail Explorer fetch message failed', [
                'message_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail email: ' . $e->getMessage()
            ], 500);
        }
    }
}
