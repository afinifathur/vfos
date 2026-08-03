<?php

namespace App\Domain\Finance;

use App\Models\Transaction;

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This service is part of the read-only architectural simulation freeze.
 */
class FinancialDecisionReportService
{
    public function __construct(
        private FinancialEventClassifier $classifier,
        private FinancialEventDecisionEngine $decisionEngine
    ) {}

    /**
     * Generates a comparative financial decision report for a given transaction.
     *
     * @param Transaction $transaction The legacy Transaction model
     * @return FinancialDecisionReport The generated report DTO
     */
    public function generateReport(Transaction $transaction): FinancialDecisionReport
    {
        $items = $transaction->transactionItems ?? collect();
        $categoryNames = [];
        $categoryTypes = [];

        foreach ($items as $item) {
            if ($item->category) {
                $categoryNames[] = $item->category->name;
                $categoryTypes[] = $item->category->type;
            }
        }

        // Build context with integer Rupiah amount (no multiplication by 100)
        $context = new TransactionClassificationContext(
            transactionType: $transaction->type,
            amountRupiah: (int)$transaction->total_amount,
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

        // 1. Map Legacy Behavior
        $legacyTypeLower = strtolower($transaction->type);
        $legacyAffectsPL = in_array($legacyTypeLower, ['income', 'expense']);
        $legacyAffectsAccounts = true; // All legacy transactions update account balances

        // 2. Map Recommended Behavior
        $recAffectsPL = $decision->includeProfitLoss;
        $recAffectsAccounts = $decision->updateAccounts;

        // 3. Compare and Compile Warnings
        $warnings = [];

        if ($legacyAffectsPL !== $recAffectsPL) {
            if ($legacyAffectsPL) {
                $warnings[] = "Legacy engine includes this in Profit & Loss. Future engine excludes it.";
            } else {
                $warnings[] = "Legacy engine excludes this from Profit & Loss. Future engine includes it.";
            }
        }

        if ($legacyAffectsAccounts !== $recAffectsAccounts) {
            if ($legacyAffectsAccounts) {
                $warnings[] = "Legacy engine updates liquid accounts. Future engine does not.";
            } else {
                $warnings[] = "Legacy engine does not update liquid accounts. Future engine recommends updating.";
            }
        }

        if ($decision->updateDebt) {
            $warnings[] = "Legacy engine records this as " . ucfirst($transaction->type) . ". Future engine recommends Liability update.";
        }

        if ($decision->updateInvestment) {
            $warnings[] = "Legacy engine records this as " . ucfirst($transaction->type) . ". Future engine recommends Investment Purchase.";
        }

        if ($decision->updateReceivable) {
            $warnings[] = "Legacy engine records this as " . ucfirst($transaction->type) . ". Future engine recommends Asset Disposal / Receivable collection.";
        }

        // Merge warnings from the decision engine itself (e.g. module missing warnings)
        $warnings = array_merge($warnings, $decision->warnings);

        // Compatibility checks: Compatible if no behavior warnings generated
        $compatible = count($warnings) === 0;

        // Compile summaries
        $categoriesStr = count($categoryNames) > 0 ? implode(', ', array_unique($categoryNames)) : '-';
        $summary = sprintf(
            "Legacy Type: %s, Categories: %s, Items: %d, Notes: '%s'",
            $transaction->type,
            $categoriesStr,
            $items->count(),
            $transaction->notes ?? ''
        );

        $compatibilitySummary = $compatible
            ? "Compatible: Legacy and recommended behaviors are identical."
            : sprintf(
                "Different Behavior: Legacy affects P/L: %s, Accounts: %s. Future affects P/L: %s, Accounts: %s, Debt: %s, Receivable: %s, Investment: %s.",
                $legacyAffectsPL ? 'Yes' : 'No',
                $legacyAffectsAccounts ? 'Yes' : 'No',
                $recAffectsPL ? 'Yes' : 'No',
                $recAffectsAccounts ? 'Yes' : 'No',
                $decision->updateDebt ? 'Yes' : 'No',
                $decision->updateReceivable ? 'Yes' : 'No',
                $decision->updateInvestment ? 'Yes' : 'No'
            );

        return new FinancialDecisionReport(
            transactionId: $transaction->id,
            legacyType: $transaction->type,
            type: $result->type,
            reason: $result->reason,
            decision: $decision,
            summary: $summary,
            warnings: $warnings,
            compatible: $compatible,
            compatibilitySummary: $compatibilitySummary
        );
    }
}
