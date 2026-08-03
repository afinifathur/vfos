<?php

namespace Tests\Unit\Finance;

use Tests\TestCase;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Category;
use App\Models\Account;
use App\Models\User;
use App\Domain\Finance\FinancialDecisionReportService;
use App\Domain\Finance\FinancialEventType;
use App\Domain\Finance\FinancialEventReason;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FinancialDecisionReportServiceTest extends TestCase
{
    use DatabaseTransactions;

    private FinancialDecisionReportService $service;
    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(FinancialDecisionReportService::class);

        // Set up test user and account
        $user = User::factory()->create();
        $this->account = Account::create([
            'user_id'        => $user->id,
            'name'           => 'Test Account',
            'type'           => 'bank',
            'account_number' => '1234',
            'total_balance'  => 0,
            'owner'          => 'afin',
        ]);
    }

    public function test_salary_income_is_compatible(): void
    {
        // Gaji category
        $category = Category::create([
            'name' => 'Gaji',
            'type' => 'income',
            'is_active' => true
        ]);

        $transaction = Transaction::create([
            'account_id' => $this->account->id,
            'type' => 'income',
            'total_amount' => 15000000,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Salary Payment'
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'category_id' => $category->id,
            'amount' => 15000000,
            'description' => 'Base salary'
        ]);

        // Reload relationships
        $transaction->load(['transactionItems.category']);

        $report = $this->service->generateReport($transaction);

        $this->assertEquals($transaction->id, $report->transactionId);
        $this->assertEquals(FinancialEventType::VALUE_CREATION, $report->type);
        $this->assertEquals(FinancialEventReason::SALARY, $report->reason);
        $this->assertTrue($report->compatible);
        $this->assertEmpty($report->warnings);
        $this->assertStringContainsString('Compatible', $report->compatibilitySummary);
    }

    public function test_investment_expense_is_different_behavior(): void
    {
        // Investasi category
        $category = Category::create([
            'name' => 'Investasi',
            'type' => 'expense',
            'is_active' => true
        ]);

        $transaction = Transaction::create([
            'account_id' => $this->account->id,
            'type' => 'expense',
            'total_amount' => 1000000,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Buy stock'
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'category_id' => $category->id,
            'amount' => 1000000,
            'description' => 'Stock purchase'
        ]);

        // Reload relationships
        $transaction->load(['transactionItems.category']);

        $report = $this->service->generateReport($transaction);

        $this->assertEquals(FinancialEventType::ASSET_ACQUISITION, $report->type);
        $this->assertEquals(FinancialEventReason::INVESTMENT_PURCHASE, $report->reason);
        $this->assertFalse($report->compatible);
        $this->assertContains('Legacy engine includes this in Profit & Loss. Future engine excludes it.', $report->warnings);
        $this->assertContains('Legacy engine records this as Expense. Future engine recommends Investment Purchase.', $report->warnings);
        $this->assertStringContainsString('Different Behavior', $report->compatibilitySummary);
    }

    public function test_debt_repayment_expense_is_different_behavior(): void
    {
        // Hutang category
        $category = Category::create([
            'name' => 'Hutang',
            'type' => 'expense',
            'is_active' => true
        ]);

        $transaction = Transaction::create([
            'account_id' => $this->account->id,
            'type' => 'expense',
            'total_amount' => 2000000,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Mortgage Principal Pay'
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'category_id' => $category->id,
            'amount' => 2000000,
            'description' => 'Mortgage'
        ]);

        // Reload relationships
        $transaction->load(['transactionItems.category']);

        $report = $this->service->generateReport($transaction);

        $this->assertEquals(FinancialEventType::LIABILITY_REDUCTION, $report->type);
        $this->assertEquals(FinancialEventReason::MORTGAGE_PRINCIPAL, $report->reason);
        $this->assertFalse($report->compatible);
        $this->assertContains('Legacy engine includes this in Profit & Loss. Future engine excludes it.', $report->warnings);
        $this->assertContains('Legacy engine records this as Expense. Future engine recommends Liability update.', $report->warnings);
    }
}
