<?php

namespace App\Domain\Finance;

class AccountClassification
{
    /**
     * Determines if an account type represents an asset.
     */
    public static function isAsset(AccountType $type): bool
    {
        return in_array($type, [
            AccountType::CASH,
            AccountType::BANK,
            AccountType::EWALLET,
            AccountType::INVESTMENT,
            AccountType::RECEIVABLE,
            AccountType::ASSET,
        ], true);
    }

    /**
     * Determines if an account type represents a liability.
     */
    public static function isLiability(AccountType $type): bool
    {
        return in_array($type, [
            AccountType::PAYABLE,
            AccountType::CREDIT_CARD,
        ], true);
    }

    /**
     * Determines if an account type is highly liquid.
     */
    public static function isLiquid(AccountType $type): bool
    {
        return in_array($type, [
            AccountType::CASH,
            AccountType::BANK,
            AccountType::EWALLET,
        ], true);
    }

    /**
     * Determines if the account type permits internal transfers.
     */
    public static function isTransferAllowed(AccountType $type): bool
    {
        return true;
    }

    /**
     * Determines if the account type contributes to Net Worth calculations.
     */
    public static function affectsNetWorth(AccountType $type): bool
    {
        return $type !== AccountType::OTHER;
    }
}
