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
        public readonly ?string $notes = null
    ) {}
}
