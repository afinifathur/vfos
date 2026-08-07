<?php

namespace App\Services\Finance\Kernel\DTO;

class AccountSummary
{
    public float $cash = 0.0;
    public float $bank = 0.0;
    public float $ewallet = 0.0;
    public float $investment = 0.0;
    public float $receivable = 0.0;
    public float $payable = 0.0;
    public float $total_assets = 0.0;
    public float $total_liabilities = 0.0;
    public float $liquid_assets = 0.0;
    public float $net_worth = 0.0;
}
