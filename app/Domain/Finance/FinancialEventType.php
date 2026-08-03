<?php

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 1 Notice: This class is part of the Phase 1 Financial Event Foundation.
 *                 It is intentionally unused in the current execution pipeline.
 *                 Full integration and behavioral changes will begin in Phase 2.
 */

namespace App\Domain\Finance;

enum FinancialEventType: string
{
    case VALUE_CREATION = 'value_creation';
    case VALUE_CONSUMPTION = 'value_consumption';
    case ASSET_SWAP = 'asset_swap';
    case LIABILITY_REDUCTION = 'liability_reduction';
    case LIABILITY_CREATION = 'liability_creation';
    case ASSET_ACQUISITION = 'asset_acquisition';
    case ASSET_DISPOSAL = 'asset_disposal';
    case VALUATION_ADJUSTMENT = 'valuation_adjustment';
    case INTERNAL_TRANSFER = 'internal_transfer';
    case UNKNOWN = 'unknown';
}
