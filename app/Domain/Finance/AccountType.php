<?php

namespace App\Domain\Finance;

enum AccountType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case EWALLET = 'ewallet';
    case INVESTMENT = 'investment';
    case RECEIVABLE = 'receivable';
    case PAYABLE = 'payable';
    case CREDIT_CARD = 'credit_card';
    case ASSET = 'asset';
    case OTHER = 'other';
}
