<?php

namespace Tests\Unit\Finance;

use Tests\TestCase;
use App\Domain\Finance\FinancialEventClassifier;
use App\Domain\Finance\FinancialEventType;
use App\Domain\Finance\FinancialEventReason;
use App\Domain\Finance\TransactionClassificationContext;

class FinancialEventClassifierTest extends TestCase
{
    private FinancialEventClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new FinancialEventClassifier();
    }

    public function test_classifies_income_with_gaji_as_salary(): void
    {
        $context = new TransactionClassificationContext(
            transactionType: 'income',
            amountRupiah: 15000000,
            metadata: [
                'category_names' => ['Gaji'],
                'item_count' => 1
            ]
        );

        $result = $this->classifier->classify($context);

        $this->assertEquals(FinancialEventType::VALUE_CREATION, $result->type);
        $this->assertEquals(FinancialEventReason::SALARY, $result->reason);
    }

    public function test_classifies_expense_with_investasi_as_investment_purchase(): void
    {
        $context = new TransactionClassificationContext(
            transactionType: 'expense',
            amountRupiah: 500000,
            metadata: [
                'category_names' => ['Investasi'],
                'item_count' => 1
            ]
        );

        $result = $this->classifier->classify($context);

        $this->assertEquals(FinancialEventType::ASSET_ACQUISITION, $result->type);
        $this->assertEquals(FinancialEventReason::INVESTMENT_PURCHASE, $result->reason);
    }

    public function test_classifies_expense_with_hutang_as_mortgage_principal(): void
    {
        $context = new TransactionClassificationContext(
            transactionType: 'expense',
            amountRupiah: 2000000,
            metadata: [
                'category_names' => ['Hutang'],
                'item_count' => 1
            ]
        );

        $result = $this->classifier->classify($context);

        $this->assertEquals(FinancialEventType::LIABILITY_REDUCTION, $result->type);
        $this->assertEquals(FinancialEventReason::MORTGAGE_PRINCIPAL, $result->reason);
    }

    public function test_classifies_income_with_hutang_as_loan_collection(): void
    {
        $context = new TransactionClassificationContext(
            transactionType: 'income',
            amountRupiah: 1000000,
            metadata: [
                'category_names' => ['Hutang'],
                'item_count' => 1
            ]
        );

        $result = $this->classifier->classify($context);

        $this->assertEquals(FinancialEventType::ASSET_DISPOSAL, $result->type);
        $this->assertEquals(FinancialEventReason::LOAN_COLLECTION, $result->reason);
    }

    public function test_classifies_withdrawal_as_cash_withdrawal(): void
    {
        $context = new TransactionClassificationContext(
            transactionType: 'withdrawal',
            amountRupiah: 150000,
            metadata: []
        );

        $result = $this->classifier->classify($context);

        $this->assertEquals(FinancialEventType::INTERNAL_TRANSFER, $result->type);
        $this->assertEquals(FinancialEventReason::CASH_WITHDRAWAL, $result->reason);
    }

    public function test_classifies_transfer_as_own_account_transfer(): void
    {
        $context = new TransactionClassificationContext(
            transactionType: 'transfer',
            amountRupiah: 200000,
            metadata: []
        );

        $result = $this->classifier->classify($context);

        $this->assertEquals(FinancialEventType::INTERNAL_TRANSFER, $result->type);
        $this->assertEquals(FinancialEventReason::OWN_ACCOUNT_TRANSFER, $result->reason);
    }

    public function test_classifies_transfer_with_deposit_notes_as_cash_deposit(): void
    {
        $context = new TransactionClassificationContext(
            transactionType: 'transfer',
            amountRupiah: 300000,
            metadata: [
                'notes' => 'Cash Setor Tunai Deposit'
            ]
        );

        $result = $this->classifier->classify($context);

        $this->assertEquals(FinancialEventType::INTERNAL_TRANSFER, $result->type);
        $this->assertEquals(FinancialEventReason::CASH_DEPOSIT, $result->reason);
    }

    public function test_classifies_unknown_type_as_unknown(): void
    {
        $context = new TransactionClassificationContext(
            transactionType: 'invalid_type',
            amountRupiah: 10000,
            metadata: []
        );

        $result = $this->classifier->classify($context);

        $this->assertEquals(FinancialEventType::UNKNOWN, $result->type);
        $this->assertEquals(FinancialEventReason::UNKNOWN, $result->reason);
    }
}
