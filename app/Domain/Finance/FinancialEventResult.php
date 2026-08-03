<?php

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This class is part of the read-only architectural simulation freeze.
 */

namespace App\Domain\Finance;

final readonly class FinancialEventResult
{
    /**
     * @param FinancialEventType $type The classified financial event type
     * @param FinancialEventReason $reason The diagnostic reason for this event
     * @param array $metadata Output metadata or split allocations calculated during classification
     */
    public function __construct(
        public FinancialEventType $type,
        public FinancialEventReason $reason,
        public array $metadata = []
    ) {}
}
