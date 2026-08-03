<?php

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This class is part of the read-only architectural simulation freeze.
 */

namespace App\Domain\Finance;

final readonly class TransactionClassificationContext
{
    /**
     * @param string $transactionType The legacy transaction type (income, expense, transfer, withdrawal)
     * @param int $amountRupiah The transaction amount represented in integer Rupiah
     * @param int|null $accountId The source account ID
     * @param int|null $toAccountId The destination account ID (for transfers)
     * @param array $metadata Additional raw metadata (category_names, category_types, item_count, notes)
     */
    public function __construct(
        public string $transactionType,
        public int $amountRupiah,
        public ?int $accountId = null,
        public ?int $toAccountId = null,
        public array $metadata = []
    ) {}
}
