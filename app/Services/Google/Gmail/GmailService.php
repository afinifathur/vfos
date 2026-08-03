<?php

namespace App\Services\Google\Gmail;

use App\Repositories\GoogleCredentialRepository;
use App\Services\Google\GoogleClientFactory;
use App\Services\Google\GoogleOAuthService;
use Google\Service\Gmail;

/**
 * Service for interacting with Gmail API and extracting message payloads.
 *
 * ARCHITECTURAL CONTRACT:
 *  - Relies on GoogleClientFactory for client instantiation.
 *  - Relies on GoogleOAuthService for refreshing expired credentials.
 *  - Purely read-only retrieval, does not write to database or finance tables.
 */
class GmailService
{
    public function __construct(
        private readonly GoogleClientFactory $clientFactory,
        private readonly GoogleOAuthService $oauthService,
        private readonly GoogleCredentialRepository $credentialRepo
    ) {}

    /**
     * List detailed messages matching the Gmail search query.
     *
     * @param  string  $query
     * @param  int     $maxResults
     * @return array   List of messages, where each message contains full metadata and payload.
     */
    public function listMessages(string $query, int $maxResults = 100): array
    {
        $client = $this->getClient();
        $gmail = new Gmail($client);

        $optParams = [
            'q' => $query,
            'maxResults' => $maxResults,
        ];

        $results = $gmail->users_messages->listUsersMessages('me', $optParams);
        
        $list = [];
        $messages = $results->getMessages();
        if ($messages) {
            foreach ($messages as $msg) {
                try {
                    $list[] = $this->getMessage($msg->getId());
                } catch (\Exception $e) {
                    // Fail-safe: if one message fails to retrieve, continue retrieving the others.
                    \Log::warning("Failed to fetch Gmail message ID: {$msg->getId()}", [
                        'exception' => $e->getMessage()
                    ]);
                }
            }
        }

        return $list;
    }

    /**
     * Search Gmail and return only message stubs containing IDs (useful for skipping duplicates before download).
     *
     * @param  string  $query
     * @param  int     $maxResults
     * @return array   List of arrays with keys: id, threadId
     */
    public function listMessageStubs(string $query, int $maxResults = 100): array
    {
        $client = $this->getClient();
        $gmail = new Gmail($client);

        $optParams = [
            'q' => $query,
            'maxResults' => $maxResults,
        ];

        $results = $gmail->users_messages->listUsersMessages('me', $optParams);
        
        $list = [];
        $messages = $results->getMessages();
        if ($messages) {
            foreach ($messages as $msg) {
                $list[] = [
                    'id' => $msg->getId(),
                    'threadId' => $msg->getThreadId(),
                ];
            }
        }

        return $list;
    }

    /**
     * Fetch a single message's details from Gmail.
     *
     * @param  string  $messageId
     * @return array   Raw message data representing the Message structure.
     */
    public function getMessage(string $messageId): array
    {
        $client = $this->getClient();
        $gmail = new Gmail($client);

        $message = $gmail->users_messages->get('me', $messageId, ['format' => 'full']);

        return json_decode(json_encode($message), true);
    }

    /**
     * Recursively traverses message MIME structure to extract HTML body.
     *
     * @param  array  $message
     * @return string|null
     */
    public function extractHtml(array $message): ?string
    {
        if (empty($message['payload'])) {
            return null;
        }

        return $this->findPartByMimeType($message['payload'], 'text/html');
    }

    /**
     * Recursively traverses message MIME structure to extract Plain Text body.
     *
     * @param  array  $message
     * @return string|null
     */
    public function extractPlainText(array $message): ?string
    {
        if (empty($message['payload'])) {
            return null;
        }

        return $this->findPartByMimeType($message['payload'], 'text/plain');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Retrieve the configured, authenticated Google Client for the user.
     * Auto-refreshes the access token if it is expired.
     */
    private function getClient(?int $userId = null): \Google\Client
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) {
            throw new \Exception('User not authenticated.');
        }

        $credential = $this->credentialRepo->findByUserAndProvider($userId, 'gmail');
        if (!$credential || !$credential->is_active) {
            throw new \Exception('Google account not connected. Please connect your account in Settings.');
        }

        if ($credential->isExpired()) {
            $credential = $this->oauthService->refreshAccessToken($credential);
        }

        $tokenArray = [
            'access_token'  => $credential->access_token,
            'refresh_token' => $credential->refresh_token,
            'expires_in'    => $credential->expires_at ? $credential->expires_at->diffInSeconds(now()) : 0,
            'created'       => $credential->last_connected_at ? $credential->last_connected_at->timestamp : now()->timestamp,
        ];

        return $this->clientFactory->makeWithToken('gmail', $tokenArray);
    }

    /**
     * Recursively search MIME parts for a specific type and return decoded data.
     */
    private function findPartByMimeType(array $part, string $mimeType): ?string
    {
        if (($part['mimeType'] ?? '') === $mimeType && !empty($part['body']['data'])) {
            return $this->decodeBase64Url($part['body']['data']);
        }

        if (!empty($part['parts'])) {
            foreach ($part['parts'] as $subpart) {
                $content = $this->findPartByMimeType($subpart, $mimeType);
                if ($content !== null) {
                    return $content;
                }
            }
        }

        return null;
    }

    /**
     * Helper to decode Base64Url string.
     */
    private function decodeBase64Url(string $data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
