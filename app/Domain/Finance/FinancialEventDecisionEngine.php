<?php

namespace App\Domain\Finance;

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This class is part of the read-only architectural simulation freeze.
 */
class FinancialEventDecisionEngine
{
    /**
     * Determines what the system should do for a classified FinancialEventResult.
     *
     * @param FinancialEventResult $result The classification outcome
     * @return FinancialEventDecision The recommended execution plan
     */
    public function decide(FinancialEventResult $result): FinancialEventDecision
    {
        $updateAccounts = false;
        $includeProfitLoss = false;
        $updateDebt = false;
        $updateReceivable = false;
        $updateInvestment = false;
        $createTransferPair = false;
        $affectsCashFlow = false;
        $affectsBalanceSheet = false;
        $affectsNetWorth = false;
        $requiresUserSelection = false;
        $targetLedgerType = null;
        $targetLedgerId = null; // Read-only simulation, always null
        $explanation = '';
        $warnings = [];
        $confidence = DecisionConfidence::LOW;

        switch ($result->type) {
            case FinancialEventType::VALUE_CREATION:
                $updateAccounts = true;
                $includeProfitLoss = true;
                $affectsCashFlow = true;
                $affectsNetWorth = true;
                $confidence = DecisionConfidence::HIGH;
                $explanation = 'Creates new value.';
                break;

            case FinancialEventType::VALUE_CONSUMPTION:
                $updateAccounts = true;
                $includeProfitLoss = true;
                $affectsCashFlow = true;
                $affectsNetWorth = true;
                $confidence = DecisionConfidence::HIGH;
                $explanation = 'Consumes economic value.';
                break;

            case FinancialEventType::INTERNAL_TRANSFER:
                $updateAccounts = true;
                $createTransferPair = true;
                $affectsCashFlow = false;
                $affectsNetWorth = false;
                $confidence = DecisionConfidence::HIGH;
                $explanation = 'Moves value between owned accounts.';
                break;

            case FinancialEventType::LIABILITY_CREATION:
                $updateAccounts = true;
                $updateDebt = true;
                $affectsBalanceSheet = true;
                $affectsCashFlow = true;
                $requiresUserSelection = true;
                $targetLedgerType = 'DEBT';
                $confidence = DecisionConfidence::MEDIUM;
                $explanation = 'Creates a new liability while increasing cash.';
                $warnings = ['Debt module not integrated.', 'Debt target required.'];
                break;

            case FinancialEventType::LIABILITY_REDUCTION:
                $updateAccounts = true;
                $updateDebt = true;
                $affectsBalanceSheet = true;
                $affectsCashFlow = true;
                $requiresUserSelection = true;
                $targetLedgerType = 'DEBT';
                $confidence = DecisionConfidence::MEDIUM;
                $explanation = 'Reduces outstanding principal.';
                $warnings = ['Debt module not integrated.', 'Debt target required.'];
                break;

            case FinancialEventType::ASSET_ACQUISITION:
                $updateAccounts = true;
                $affectsBalanceSheet = true;
                $affectsCashFlow = true;
                $requiresUserSelection = true;
                $confidence = DecisionConfidence::MEDIUM;
                if ($result->reason === FinancialEventReason::LOAN_COLLECTION) {
                    $updateReceivable = true;
                    $targetLedgerType = 'RECEIVABLE';
                    $explanation = 'Converts cash into another asset.';
                    $warnings = ['Receivable module not integrated.', 'Receivable target required.'];
                } else {
                    $updateInvestment = true;
                    $targetLedgerType = 'INVESTMENT';
                    $explanation = 'Converts cash into another asset.';
                    $warnings = ['Investment module not integrated.', 'Investment target required.'];
                }
                break;

            case FinancialEventType::ASSET_DISPOSAL:
                $updateAccounts = true;
                $affectsBalanceSheet = true;
                $affectsCashFlow = true;
                $requiresUserSelection = true;
                $confidence = DecisionConfidence::MEDIUM;
                if ($result->reason === FinancialEventReason::LOAN_COLLECTION) {
                    $updateReceivable = true;
                    $targetLedgerType = 'RECEIVABLE';
                    $explanation = 'Converts asset into cash.';
                    $warnings = ['Receivable module not integrated.', 'Receivable target required.'];
                } else {
                    $updateInvestment = true;
                    $targetLedgerType = 'INVESTMENT';
                    $explanation = 'Converts asset into cash.';
                    $warnings = ['Investment module not integrated.', 'Investment target required.'];
                }
                break;

            case FinancialEventType::VALUATION_ADJUSTMENT:
                $updateInvestment = true;
                $affectsBalanceSheet = true;
                $affectsNetWorth = true;
                $requiresUserSelection = true;
                $targetLedgerType = 'INVESTMENT';
                $confidence = DecisionConfidence::MEDIUM;
                $explanation = 'Changes valuation only.';
                $warnings = ['Investment module not integrated.', 'Investment target required.'];
                break;

            case FinancialEventType::UNKNOWN:
            default:
                $confidence = DecisionConfidence::LOW;
                $explanation = 'Unable to classify.';
                $warnings = ['Unknown classification.', 'Manual review recommended.'];
                break;
        }

        return new FinancialEventDecision(
            type: $result->type,
            reason: $result->reason,
            confidence: $confidence,
            updateAccounts: $updateAccounts,
            includeProfitLoss: $includeProfitLoss,
            updateDebt: $updateDebt,
            updateReceivable: $updateReceivable,
            updateInvestment: $updateInvestment,
            createTransferPair: $createTransferPair,
            affectsCashFlow: $affectsCashFlow,
            affectsBalanceSheet: $affectsBalanceSheet,
            affectsNetWorth: $affectsNetWorth,
            requiresUserSelection: $requiresUserSelection,
            targetLedgerType: $targetLedgerType,
            targetLedgerId: $targetLedgerId,
            explanation: $explanation,
            warnings: $warnings
        );
    }
}
