<?php

namespace App\Domain\Finance\ValueObject;

class NetWorth
{
    private Money $totalAssets;
    private Money $totalLiabilities;

    public function __construct(Money $totalAssets, Money $totalLiabilities)
    {
        $this->totalAssets = $totalAssets;
        $this->totalLiabilities = $totalLiabilities;
    }

    public function getTotalAssets(): Money
    {
        return $this->totalAssets;
    }

    public function getTotalLiabilities(): Money
    {
        return $this->totalLiabilities;
    }

    public function getNetWorth(): Money
    {
        return $this->totalAssets->subtract($this->totalLiabilities);
    }
}
