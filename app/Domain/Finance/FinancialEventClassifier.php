<?php

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This class is part of the read-only architectural simulation freeze.
 */

namespace App\Domain\Finance;

class FinancialEventClassifier
{
    /**
     * Classifies a transaction's context into a specific FinancialEventType and FinancialEventReason.
     *
     * @param TransactionClassificationContext $context Contextual details of the transaction
     * @return FinancialEventResult The classification result wrapping type, reason, and metadata
     */
    public function classify(TransactionClassificationContext $context): FinancialEventResult
    {
        $legacyType = strtolower($context->transactionType);
        $categoryNames = $context->metadata['category_names'] ?? [];
        $notes = strtolower($context->metadata['notes'] ?? '');

        // Convert category names to lowercase for comparison
        $categoriesLower = array_map('strtolower', $categoryNames);

        $type = FinancialEventType::UNKNOWN;
        $reason = FinancialEventReason::UNKNOWN;

        if ($legacyType === 'income') {
            if (in_array('gaji', $categoriesLower)) {
                $type = FinancialEventType::VALUE_CREATION;
                $reason = FinancialEventReason::SALARY;
            } elseif (in_array('hutang', $categoriesLower)) {
                // Legacy income under debt category represents Loan Collection (Receivable asset disposal)
                $type = FinancialEventType::ASSET_DISPOSAL;
                $reason = FinancialEventReason::LOAN_COLLECTION;
            } else {
                // Default to VALUE_CREATION / SALARY for other income
                $type = FinancialEventType::VALUE_CREATION;
                $reason = FinancialEventReason::SALARY;
            }
        } elseif ($legacyType === 'expense') {
            if (in_array('investasi', $categoriesLower)) {
                $type = FinancialEventType::ASSET_ACQUISITION;
                $reason = FinancialEventReason::INVESTMENT_PURCHASE;
            } elseif (in_array('hutang', $categoriesLower)) {
                $type = FinancialEventType::LIABILITY_REDUCTION;
                $reason = FinancialEventReason::MORTGAGE_PRINCIPAL;
            } else {
                $type = FinancialEventType::VALUE_CONSUMPTION;
                $reason = FinancialEventReason::MERCHANT_PAYMENT;
            }
        } elseif ($legacyType === 'withdrawal') {
            $type = FinancialEventType::INTERNAL_TRANSFER;
            $reason = FinancialEventReason::CASH_WITHDRAWAL;
        } elseif ($legacyType === 'transfer') {
            $type = FinancialEventType::INTERNAL_TRANSFER;
            if (str_contains($notes, 'deposit') || str_contains($notes, 'setor')) {
                $reason = FinancialEventReason::CASH_DEPOSIT;
            } else {
                $reason = FinancialEventReason::OWN_ACCOUNT_TRANSFER;
            }
        }

        return new FinancialEventResult($type, $reason, [
            'category_names' => $categoryNames,
            'notes' => $context->metadata['notes'] ?? null
        ]);
    }
}
