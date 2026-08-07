<?php

namespace App\DTO;

use App\Enums\ConfidenceLevel;
use Carbon\Carbon;

/**
 * Immutable DTO representing a fully normalized transaction.
 * Stored amounts are represented as integer minor units (no floating points).
 */
class ParsedTransaction
{
    public function __construct(
        public readonly ?string $merchant,
        public readonly int $amount, // Minor units
        public readonly Carbon $transactionDate,
        public readonly ?string $reference,
        public readonly string $bank,
        public readonly string $flow, // IN, OUT
        public readonly string $type, // TRANSFER, PAYMENT, etc.
        public readonly ?string $sourceAccount,
        public readonly ?string $destinationAccount,
        public readonly string $rawAmount,
        public readonly string $status, // SUCCESS, etc.
        public readonly ConfidenceLevel $confidence,
        public readonly ?string $notes = null,
        // ── Metadata fields (nullable, backward-compatible) ───────────────────
        /**
         * Template identifier indicating which parser template produced this transaction.
         * Examples: BCA_QRIS, BCA_TRANSFER, BCA_VIRTUAL_ACCOUNT, BCA_CASH_DEPOSIT,
         *           BCA_CASH_WITHDRAW, MANDIRI_TRANSFER, MANDIRI_BIFAST, MANDIRI_QRIS, MANDIRI_TOPUP
         * Used for debugging, analytics, and future parser maintenance.
         */
        public readonly ?string $template = null,
        /**
         * Human-readable name of the counterparty (sender or recipient).
         * Extracted from fields like "Nama Penerima" / "Nama Pengirim" in bank emails.
         * Used by TransactionDescriptionBuilder to generate concise descriptions.
         */
        public readonly ?string $counterparty = null,
        /**
         * Non-persisted metadata for importer/reporting use.
         */
        public readonly array $metadata = [],
    ) {}
}
