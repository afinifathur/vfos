<?php

namespace App\Services\Google\Gmail;

/**
 * Centralizes finance-email identification for Gmail synchronization.
 *
 * Purpose
 * ───────
 * The Gmail Inbox module is a Finance Connector Cache — not an email archive.
 * Only transactional finance emails (bank notifications, payment receipts) should
 * ever reach the InboxMessage table. This filter enforces that contract at two points:
 *
 *   1. Query-level  — buildDefaultQuery() produces a Gmail search that already
 *                     restricts results to known finance senders only.
 *   2. Message-level — isFinanceEmail() is a strict second-pass whitelist check
 *                      on downloaded headers to guarantee non-finance emails are
 *                      never stored.
 *
 * Supported senders (whitelist)
 * ─────────────────────────────
 * BCA      bca@bca.co.id
 * Mandiri  noreply.livin@bankmandiri.co.id
 *
 * Other banks can be added to FINANCE_SENDERS and buildDefaultQuery() later.
 *
 * Design: whitelist-only. No blacklist. No subject-keyword fallback.
 */
class GmailFinanceFilter
{
    /**
     * Exact sender addresses accepted as finance emails.
     * These are matched case-insensitively against the full "From" header.
     */
    private const FINANCE_SENDERS = [
        'bca@bca.co.id',
        'noreply.livin@bankmandiri.co.id',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the default Gmail search query for finance-only synchronization.
     *
     * Uses a whitelist of known finance senders so only relevant emails are
     * fetched from the Gmail API. No time restriction is added by default;
     * the UI may append one via presets.
     */
    public function buildDefaultQuery(string $timePeriod = ''): string
    {
        $fromClause = 'from:(' . implode(' OR ', self::FINANCE_SENDERS) . ')';

        return trim("{$fromClause} {$timePeriod}");
    }

    /**
     * Determine whether a downloaded message qualifies as a finance email.
     *
     * Whitelist-only: the sender address must exactly match one of the
     * FINANCE_SENDERS list. If it does not, the message is rejected — no
     * InboxMessage record is created.
     *
     * @param string $from     The "From" header value
     * @param string $subject  The "Subject" header value (unused; reserved for future use)
     */
    public function isFinanceEmail(string $from, string $subject): bool
    {
        $fromLower = strtolower($from);

        foreach (self::FINANCE_SENDERS as $sender) {
            if (str_contains($fromLower, strtolower($sender))) {
                return true;
            }
        }

        return false;
    }
}
