<?php

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This class is part of the read-only architectural simulation freeze.
 */

namespace App\Domain\Finance;

use App\Models\Transaction;

class TransactionClassificationService
{
    public function __construct(
        private FinancialEventClassifier $classifier,
        private FinancialEventDecisionEngine $decisionEngine
    ) {}

    /**
     * Classifies and decides execution parameters for an existing Transaction model.
     *
     * @param Transaction $transaction The legacy Transaction model
     * @return FinancialEventAnalysis The combined classification and decision analysis
     */
    public function classify(Transaction $transaction): FinancialEventAnalysis
    {
        // Gather transaction items details for metadata
        $items = $transaction->transactionItems ?? collect();
        $categoryNames = [];
        $categoryTypes = [];

        foreach ($items as $item) {
            if ($item->category) {
                $categoryNames[] = $item->category->name;
                $categoryTypes[] = $item->category->type;
            }
        }

        $context = new TransactionClassificationContext(
            transactionType: $transaction->type,
            amountRupiah: (int)$transaction->total_amount, // Rupiah values represent the integer unit in IDR (no multiplication by 100)
            accountId: $transaction->account_id,
            toAccountId: $transaction->to_account_id,
            metadata: [
                'item_count' => $items->count(),
                'category_names' => $categoryNames,
                'category_types' => $categoryTypes,
                'notes' => $transaction->notes,
            ]
        );

        $result = $this->classifier->classify($context);
        $decision = $this->decisionEngine->decide($result);

        return new FinancialEventAnalysis($result, $decision);
    }
}
