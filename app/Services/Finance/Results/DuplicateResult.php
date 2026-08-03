<?php

namespace App\Services\Finance\Results;

/**
 * Specialized import outcome signifying a skipped duplicate transaction.
 */
class DuplicateResult extends ImportResult
{
    public function __construct(int $transactionId, string $message = 'Duplicate ignored')
    {
        parent::__construct('DUPLICATE', $transactionId, $message);
    }
}
