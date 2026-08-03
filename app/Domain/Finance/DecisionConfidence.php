<?php

namespace App\Domain\Finance;

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This enum is part of the read-only architectural simulation freeze.
 */
enum DecisionConfidence: string
{
    case HIGH = 'HIGH';
    case MEDIUM = 'MEDIUM';
    case LOW = 'LOW';
}
