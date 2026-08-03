<?php

namespace App\Domain\Finance;

/**
 * Package: App\Domain\Finance
 * Description: The App\Domain\Finance package is the future Financial Engine and eventual
 *              single source of truth for financial event processing in vFOS.
 *
 * Phase 3 Notice: This enum is part of the read-only architectural simulation freeze.
 */
enum FinancialEventReason: string
{
    case SALARY = 'Salary';
    case MERCHANT_PAYMENT = 'Merchant Payment';
    case OWN_ACCOUNT_TRANSFER = 'Own Account Transfer';
    case MORTGAGE_PRINCIPAL = 'Mortgage Principal';
    case LOAN_COLLECTION = 'Loan Collection';
    case INVESTMENT_PURCHASE = 'Investment Purchase';
    case CASH_DEPOSIT = 'Cash Deposit';
    case CASH_WITHDRAWAL = 'Cash Withdrawal';
    case UNKNOWN = 'Unknown';
}
