<?php

namespace App\Services\Finance\Results;

/**
 * Represents the result of a financial transaction import operation.
 */
class ImportResult
{
    public function __construct(
        public readonly string $status, // 'SUCCESS', 'DUPLICATE', 'FAILED'
        public readonly ?int $transactionId = null,
        public readonly ?string $message = null
    ) {}

    public static function success(int $transactionId, string $message = 'Imported successfully'): self
    {
        return new self('SUCCESS', $transactionId, $message);
    }

    public static function duplicate(int $transactionId, string $message = 'Duplicate ignored'): DuplicateResult
    {
        return new DuplicateResult($transactionId, $message);
    }
}
