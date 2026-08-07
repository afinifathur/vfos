<?php

namespace App\Services\Finance;

use App\DTO\ParsedTransaction;
use App\Models\Account;
use App\Services\Finance\MerchantNormalizer;

/**
 * Generates concise, human-readable transaction descriptions from structured ParsedTransaction fields.
 *
 * Philosophy
 * ──────────
 * This is the single source of truth for all user-facing transaction labels.
 * Parsers extract facts; this class builds text.
 *
 * Rules
 * ─────
 *  • Never include reference numbers in descriptions.
 *  • Never use raw email body text.
 *  • Build labels from: template, merchant, counterparty, keterangan (notes), flow.
 *  • Max 60 characters; truncate gracefully.
 *
 * Label examples by template
 * ──────────────────────────
 *  BCA_QRIS              + merchant "INDOMARET JAYA"     → "QRIS INDOMARET JAYA"
 *  BCA_QRIS              + merchant "WARUNG MANADO"       → "QRIS Warung Manado"
 *  BCA_TRANSFER OUT      + counterparty "INAYAH TOYIBAH"  → "Transfer to Inayah Toyibah"
 *  BCA_TRANSFER IN       + notes "Kost Agustus"           → "Kost Agustus"
 *  BCA_TRANSFER IN       + counterparty "PAYROLL SYSTEM"  → "Transfer from Payroll System"
 *  BCA_TRANSFER (own)    (no external counterparty)       → "Transfer Internal"
 *  BCA_VIRTUAL_ACCOUNT   + company "by.U SuperKaget3GB"   → "by.U SuperKaget3GB"
 *  BCA_CASH_DEPOSIT                                       → "Cash Deposit"
 *  BCA_CASH_WITHDRAW                                      → "Cash Withdrawal"
 *  MANDIRI_QRIS          + merchant "TOKOPEDIA"           → "QRIS Tokopedia"
 *  MANDIRI_BIFAST OUT    + counterparty "INAYAH"          → "Transfer to Inayah"
 *  MANDIRI_TOPUP         + merchant "ShopeePay"           → "Top Up ShopeePay"
 *  (fallback)                                             → merchant name or "Unknown Transaction"
 */
class TransactionDescriptionBuilder
{
    private const MAX_LENGTH = 60;

    private MerchantNormalizer $normalizer;

    public function __construct(MerchantNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Build the primary concise description (used as TransactionItem::description).
     */
    public function build(ParsedTransaction $tx): string
    {
        $label = match ($tx->template) {
            'BCA_CASH_DEPOSIT'     => 'Cash Deposit',
            'BCA_CASH_WITHDRAW'    => 'Cash Withdrawal',
            'BCA_QRIS'             => $this->qrisLabel($tx),
            'BCA_VIRTUAL_ACCOUNT'  => $this->virtualAccountLabel($tx),
            'BCA_TRANSFER'         => $this->transferLabel($tx),
            'MANDIRI_QRIS'         => $this->qrisLabel($tx),
            'MANDIRI_TOPUP'        => $this->topUpLabel($tx),
            'MANDIRI_TRANSFER',
            'MANDIRI_BIFAST'       => $this->transferLabel($tx),
            default                => $this->fallbackLabel($tx),
        };

        return $this->clean($label);
    }

    /**
     * Build the transaction-level notes (used as Transaction::notes).
     * Same value as build() for now; separated for future extensibility.
     */
    public function buildNotes(ParsedTransaction $tx): string
    {
        return $this->build($tx);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private label builders
    // ─────────────────────────────────────────────────────────────────────────

    private function getMerchant(ParsedTransaction $tx): string
    {
        return $this->normalizer->normalizeMerchant($tx->merchant ?? '');
    }

    private function getCounterparty(ParsedTransaction $tx): string
    {
        return $this->normalizer->normalizeMerchant($tx->counterparty ?? '');
    }

    private function qrisLabel(ParsedTransaction $tx): string
    {
        if (!empty($tx->notes) && strlen(trim($tx->notes)) >= 3) {
            return trim($tx->notes);
        }
        $merchant = $this->getMerchant($tx);
        if ($merchant === '' || strtolower($merchant) === 'unknown merchant') {
            return 'QRIS Payment';
        }
        return 'QRIS ' . $this->smartCase($merchant);
    }

    private function virtualAccountLabel(ParsedTransaction $tx): string
    {
        if (!empty($tx->notes) && strlen(trim($tx->notes)) >= 3) {
            return trim($tx->notes);
        }

        $parts = array_values(array_filter([
            $this->smartCase($this->getMerchant($tx)),
            $this->smartCase($this->getCounterparty($tx)),
        ]));

        if (!empty($parts)) {
            return $this->clean(implode(' ', $parts));
        }
        return 'Virtual Account Payment';
    }

    private function transferLabel(ParsedTransaction $tx): string
    {
        // Priority 1: meaningful remarks/notes → use as-is (e.g. "Kost Agustus")
        if (!empty($tx->notes) && strlen(trim($tx->notes)) >= 3) {
            return trim($tx->notes);
        }

        // Priority 2: Check if this is an internal transfer
        if ($this->isInternalTransfer($tx)) {
            return 'Transfer Internal';
        }

        // Priority 3: counterparty name
        $counterparty = $this->getCounterparty($tx);
        if ($counterparty !== '' && strtolower($counterparty) !== 'unknown merchant') {
            $name = $this->smartCase($counterparty);
            return $tx->flow === 'IN'
                ? "Transfer from {$name}"
                : "Transfer to {$name}";
        }

        // Priority 4: merchant holds an account number or placeholder → internal transfer
        return 'Transfer Internal';
    }

    private function topUpLabel(ParsedTransaction $tx): string
    {
        if (!empty($tx->notes) && strlen(trim($tx->notes)) >= 3) {
            return trim($tx->notes);
        }

        $merchant = $this->getMerchant($tx);
        if ($merchant === '' || strtolower($merchant) === 'unknown merchant') {
            return 'Top Up';
        }
        return 'Top Up ' . $this->smartCase($merchant);
    }

    private function fallbackLabel(ParsedTransaction $tx): string
    {
        // Try meaningful notes first
        if (!empty($tx->notes) && strlen(trim($tx->notes)) >= 3) {
            return trim($tx->notes);
        }

        // Try counterparty
        $counterparty = $this->getCounterparty($tx);
        if ($counterparty !== '' && strtolower($counterparty) !== 'unknown merchant') {
            return $this->smartCase($counterparty);
        }

        // Try raw merchant name
        $merchant = $this->getMerchant($tx);
        if ($merchant !== '' && strtolower($merchant) !== 'unknown merchant') {
            return $this->smartCase($merchant);
        }

        return 'Unknown Transaction';
    }

    /**
     * Detects if the transfer is internal using multiple signals.
     */
    private function isInternalTransfer(ParsedTransaction $tx): bool
    {
        // Signal 1: destination account number matches known owned accounts
        $destAcc = $tx->destinationAccount;
        if (!empty($destAcc)) {
            $digits = preg_replace('/[^0-9]/', '', $destAcc);
            if (!empty($digits)) {
                $isOwnAccount = Account::where(function ($q) use ($digits) {
                    $q->where('account_number', $digits)
                      ->orWhereRaw('RIGHT(account_number, 4) = ?', [substr($digits, -4)])
                      ->orWhereRaw('RIGHT(?, 4) = account_number', [$digits]);
                })->exists();
                if ($isOwnAccount) {
                    return true;
                }
            }
        }

        // Signal 2: source account number matches known owned accounts
        $srcAcc = $tx->sourceAccount;
        if (!empty($srcAcc)) {
            $digits = preg_replace('/[^0-9]/', '', $srcAcc);
            if (!empty($digits)) {
                $isOwnAccount = Account::where(function ($q) use ($digits) {
                    $q->where('account_number', $digits)
                      ->orWhereRaw('RIGHT(account_number, 4) = ?', [substr($digits, -4)])
                      ->orWhereRaw('RIGHT(?, 4) = account_number', [$digits]);
                })->exists();
                if ($isOwnAccount) {
                    return true;
                }
            }
        }

        // Signal 3: counterparty contains my own identity
        $counterparty = $this->getCounterparty($tx);
        if ($counterparty !== '') {
            $lowerCounterparty = strtolower($counterparty);
            $myIdentityTokens = ['moh afini', 'afini', 'fathur', 'rozi'];
            foreach ($myIdentityTokens as $token) {
                if (str_contains($lowerCounterparty, $token)) {
                    return true;
                }
            }

            // Signal 4: match against known accounts' owners and names
            $accounts = Account::all(['name', 'owner']);
            foreach ($accounts as $acc) {
                foreach ([$acc->name, $acc->owner] as $candidate) {
                    $candidateLower = strtolower(trim((string)$candidate));
                    if ($candidateLower !== '' && (str_contains($lowerCounterparty, $candidateLower) || str_contains($candidateLower, $lowerCounterparty))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convert a string to Title Case (each word capitalised).
     * Handles ALL-CAPS names from bank emails gracefully.
     */
    private function titleCase(string $value): string
    {
        return ucwords(strtolower(trim($value)));
    }

    private function smartCase(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $trimmed) ?? $trimmed;
        if (preg_match('/[a-z]/', $normalized) && preg_match('/[A-Z]/', $normalized)) {
            return $normalized;
        }

        $specials = [
            'by.u' => 'by.U',
            'shopeepay' => 'ShopeePay',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'qris' => 'QRIS',
        ];

        $lower = strtolower($normalized);
        if (isset($specials[$lower])) {
            return $specials[$lower];
        }

        return $this->titleCase($normalized);
    }

    /**
     * Trim and enforce max length (hard-truncate with ellipsis).
     */
    private function clean(string $label): string
    {
        $label = trim($label);
        if (strlen($label) > self::MAX_LENGTH) {
            $label = substr($label, 0, self::MAX_LENGTH - 1) . '…';
        }
        return $label;
    }
}
