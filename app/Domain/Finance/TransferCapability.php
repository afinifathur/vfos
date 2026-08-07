<?php

namespace App\Domain\Finance;

class TransferCapability
{
    private static array $matrix = [
        'cash' => ['receive' => true, 'send' => true, 'transfer' => true, 'income' => true, 'expense' => true, 'net_worth' => true],
        'bank' => ['receive' => true, 'send' => true, 'transfer' => true, 'income' => true, 'expense' => true, 'net_worth' => true],
        'ewallet' => ['receive' => true, 'send' => true, 'transfer' => true, 'income' => true, 'expense' => true, 'net_worth' => true],
        'investment' => ['receive' => true, 'send' => true, 'transfer' => true, 'income' => false, 'expense' => false, 'net_worth' => true],
        'receivable' => ['receive' => true, 'send' => true, 'transfer' => true, 'income' => false, 'expense' => false, 'net_worth' => true],
        'payable' => ['receive' => true, 'send' => true, 'transfer' => true, 'income' => false, 'expense' => false, 'net_worth' => true],
        'credit_card' => ['receive' => true, 'send' => true, 'transfer' => true, 'income' => false, 'expense' => true, 'net_worth' => true],
        'asset' => ['receive' => false, 'send' => false, 'transfer' => true, 'income' => false, 'expense' => false, 'net_worth' => true],
        'other' => ['receive' => true, 'send' => true, 'transfer' => true, 'income' => true, 'expense' => true, 'net_worth' => false],
    ];

    public static function canReceive(AccountType $type): bool
    {
        return self::$matrix[$type->value]['receive'] ?? false;
    }

    public static function canSend(AccountType $type): bool
    {
        return self::$matrix[$type->value]['send'] ?? false;
    }

    public static function canTransfer(AccountType $type): bool
    {
        return self::$matrix[$type->value]['transfer'] ?? false;
    }

    public static function canEarnIncome(AccountType $type): bool
    {
        return self::$matrix[$type->value]['income'] ?? false;
    }

    public static function canIncurExpense(AccountType $type): bool
    {
        return self::$matrix[$type->value]['expense'] ?? false;
    }

    public static function contributesToNetWorth(AccountType $type): bool
    {
        return self::$matrix[$type->value]['net_worth'] ?? false;
    }
}
