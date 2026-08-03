<?php

namespace App\Domain\Finance;

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This class is part of the read-only architectural simulation freeze.
 */
final readonly class FinancialDecisionReport
{
    /**
     * @param int $transactionId Legacy transaction primary key
     * @param string $legacyType Legacy type (income, expense, transfer, withdrawal)
     * @param FinancialEventType $type Classified event type
     * @param FinancialEventReason $reason Classified diagnostic reason
     * @param FinancialEventDecision $decision Recommended execution plan
     * @param string $summary Human-readable transaction characteristics summary
     * @param array $warnings Aggregated warnings from classification and decisioning
     * @param bool $compatible True if legacy execution is compatible with the new recommended plan
     * @param string $compatibilitySummary Detailed breakdown of behavioral alignment or differences
     */
    public function __construct(
        public int $transactionId,
        public string $legacyType,
        public FinancialEventType $type,
        public FinancialEventReason $reason,
        public FinancialEventDecision $decision,
        public string $summary,
        public array $warnings,
        public bool $compatible,
        public string $compatibilitySummary
    ) {}
}
