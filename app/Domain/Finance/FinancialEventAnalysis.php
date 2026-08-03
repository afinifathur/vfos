<?php

namespace App\Domain\Finance;

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This class is part of the read-only architectural simulation freeze.
 */
final readonly class FinancialEventAnalysis
{
    /**
     * @param FinancialEventResult $result The classification outcome
     * @param FinancialEventDecision $decision The recommended execution plan
     */
    public function __construct(
        public FinancialEventResult $result,
        public FinancialEventDecision $decision
    ) {}
}
