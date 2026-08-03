<?php

namespace App\Domain\Finance;

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This class is part of the read-only architectural simulation freeze.
 */
final readonly class FinancialEventDecision
{
    /**
     * @param FinancialEventType $type The target financial event type
     * @param FinancialEventReason $reason The target diagnostic reason
     * @param DecisionConfidence $confidence The confidence level of this decision
     * @param bool $updateAccounts Whether it updates liquid bank account balances
     * @param bool $includeProfitLoss Whether this event is included in the Profit & Loss statement
     * @param bool $updateDebt Whether this event updates the Debt sub-ledger
     * @param bool $updateReceivable Whether this event updates the Receivable sub-ledger
     * @param bool $updateInvestment Whether this event updates the Investment sub-ledger
     * @param bool $createTransferPair Whether this event triggers creation of a double-entry transfer pair
     * @param bool $affectsCashFlow Whether this event changes aggregate cash flow
     * @param bool $affectsBalanceSheet Whether this event changes balance sheet/sub-ledger state
     * @param bool $affectsNetWorth Whether this event changes the user's Net Worth
     * @param bool $requiresUserSelection Whether this event requires manual sub-ledger target mapping by the user
     * @param string|null $targetLedgerType The target ledger classification (DEBT, RECEIVABLE, INVESTMENT, or null)
     * @param int|null $targetLedgerId The specific target ledger ID if resolved, otherwise null
     * @param string $explanation Human-readable explanation of why this decision was made
     * @param array $warnings Diagnostic warnings or blocker notices
     */
    public function __construct(
        public FinancialEventType $type,
        public FinancialEventReason $reason,
        public DecisionConfidence $confidence,
        public bool $updateAccounts,
        public bool $includeProfitLoss,
        public bool $updateDebt,
        public bool $updateReceivable,
        public bool $updateInvestment,
        public bool $createTransferPair,
        public bool $affectsCashFlow,
        public bool $affectsBalanceSheet,
        public bool $affectsNetWorth,
        public bool $requiresUserSelection,
        public ?string $targetLedgerType,
        public ?int $targetLedgerId,
        public string $explanation,
        public array $warnings = []
    ) {}
}
