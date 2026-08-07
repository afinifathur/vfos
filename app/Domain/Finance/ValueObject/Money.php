<?php

namespace App\Domain\Finance\ValueObject;

use InvalidArgumentException;

class Money
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'IDR')
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): Money
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->getAmount(), $this->currency);
    }

    public function subtract(Money $other): Money
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->getAmount(), $this->currency);
    }

    public function formatted(): string
    {
        if ($this->currency === 'IDR') {
            return 'Rp ' . number_format($this->amount, 0, ',', '.');
        }
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->getCurrency()}");
        }
    }
}
