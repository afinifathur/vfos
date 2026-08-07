<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\Finance\Kernel\FinancialKernelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use InvalidArgumentException;

class FinancialKernelServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialKernelService $kernelService;
    private User $user;
    private Account $bankAccount;
    private Account $walletAccount;
    private Category $incomeCategory;
    private Category $expenseCategory;
    private Category $transferCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->kernelService = app(FinancialKernelService::class);

        // Create and authenticate user
        $this->user = User::factory()->create(['role' => 'user']);
        $this->actingAs($this->user);

        // Create base accounts
        $this->bankAccount = Account::create([
            'user_id'        => $this->user->id,
            'name'           => 'Main Bank',
            'type'           => 'bank',
            'account_number' => '1111',
            'initial_balance'=> 1000000.00,
            'total_balance'  => 1000000.00,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        $this->walletAccount = Account::create([
            'user_id'        => $this->user->id,
            'name'           => 'Main Wallet',
            'type'           => 'ewallet',
            'account_number' => '2222',
            'initial_balance'=> 200000.00,
            'total_balance'  => 200000.00,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        // Create base categories
        $this->incomeCategory = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'gaji',
            'type'    => 'income',
            'owner'   => 'afin',
        ]);

        $this->expenseCategory = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'makan',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);

        $this->transferCategory = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'transfer',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);
    }

    /**
     * Test service instantiation.
     */
    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(FinancialKernelService::class, $this->kernelService);
    }

    /**
     * Test createIncome API.
     */
    public function test_create_income_creates_records_and_updates_balance(): void
    {
        $tx = $this->kernelService->createIncome(
            $this->bankAccount->id,
            150000.00,
            '2026-08-06',
            'Monthly salary bonus',
            ['category_id' => $this->incomeCategory->id]
        );

        $this->assertNotNull($tx);
        $this->assertEquals('income', $tx->type);
        $this->assertEquals(150000.00, $tx->total_amount);
        $this->assertEquals($this->bankAccount->id, $tx->account_id);
        $this->assertEquals('Monthly salary bonus', $tx->notes);

        // Verify transaction item exists
        $this->assertEquals(1, $tx->transactionItems()->count());
        $item = $tx->transactionItems()->first();
        $this->assertEquals($this->incomeCategory->id, $item->category_id);
        $this->assertEquals(150000.00, $item->amount);

        // Verify balance recalculation was triggered
        $this->bankAccount->refresh();
        $this->assertEquals(1150000.00, $this->bankAccount->total_balance);
    }

    /**
     * Test createIncome validation.
     */
    public function test_create_income_fails_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->kernelService->createIncome(
            $this->bankAccount->id,
            -500.00,
            '2026-08-06'
        );
    }

    /**
     * Test createExpense API.
     */
    public function test_create_expense_creates_records_and_updates_balance(): void
    {
        $tx = $this->kernelService->createExpense(
            $this->bankAccount->id,
            50000.00,
            '2026-08-06',
            'Lunch at warung',
            ['category_id' => $this->expenseCategory->id]
        );

        $this->assertNotNull($tx);
        $this->assertEquals('expense', $tx->type);
        $this->assertEquals(50000.00, $tx->total_amount);
        $this->assertEquals('Lunch at warung', $tx->notes);

        // Verify transaction item exists
        $this->assertEquals(1, $tx->transactionItems()->count());
        $item = $tx->transactionItems()->first();
        $this->assertEquals($this->expenseCategory->id, $item->category_id);

        // Verify balance recalculation was triggered
        $this->bankAccount->refresh();
        $this->assertEquals(950000.00, $this->bankAccount->total_balance);
    }

    /**
     * Test createExpense validation.
     */
    public function test_create_expense_fails_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->kernelService->createExpense(
            $this->bankAccount->id,
            -100.00,
            '2026-08-06'
        );
    }

    /**
     * Test createTransfer API.
     */
    public function test_create_transfer_creates_records_and_updates_both_balances(): void
    {
        $tx = $this->kernelService->createTransfer(
            $this->bankAccount->id,
            $this->walletAccount->id,
            100000.00,
            '2026-08-06',
            'Wallet topup',
            ['category_id' => $this->transferCategory->id]
        );

        $this->assertNotNull($tx);
        $this->assertEquals('transfer', $tx->type);
        $this->assertEquals(100000.00, $tx->total_amount);
        $this->assertEquals($this->bankAccount->id, $tx->account_id);
        $this->assertEquals($this->walletAccount->id, $tx->to_account_id);

        // Verify balances on both accounts updated
        $this->bankAccount->refresh();
        $this->walletAccount->refresh();

        $this->assertEquals(900000.00, $this->bankAccount->total_balance);
        $this->assertEquals(300000.00, $this->walletAccount->total_balance);
    }

    /**
     * Test createTransfer validation.
     */
    public function test_create_transfer_fails_on_same_accounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->kernelService->createTransfer(
            $this->bankAccount->id,
            $this->bankAccount->id,
            5000.00,
            '2026-08-06'
        );
    }

    /**
     * Test createAdjustment API.
     */
    public function test_create_adjustment_positive_and_negative(): void
    {
        // Positive adjustment (Credit)
        $txCredit = $this->kernelService->createAdjustment(
            $this->bankAccount->id,
            50000.00,
            '2026-08-06',
            'Interest adjustment'
        );
        $this->assertEquals('income', $txCredit->type);
        $this->assertEquals(50000.00, $txCredit->total_amount);

        // Negative adjustment (Debit)
        $txDebit = $this->kernelService->createAdjustment(
            $this->bankAccount->id,
            -20000.00,
            '2026-08-06',
            'Tax adjustment'
        );
        $this->assertEquals('expense', $txDebit->type);
        $this->assertEquals(20000.00, $txDebit->total_amount);

        // Verify final balance
        $this->bankAccount->refresh();
        $this->assertEquals(1030000.00, $this->bankAccount->total_balance);
    }

    /**
     * Test balance refreshing APIs.
     */
    public function test_refresh_balance_and_calculate_net_worth(): void
    {
        // Add manual transaction bypassing the service to check manual refresh
        $tx = Transaction::create([
            'account_id'       => $this->bankAccount->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'total_amount'     => 120000.00,
        ]);
        $tx->transactionItems()->create([
            'category_id' => $this->expenseCategory->id,
            'amount'      => 120000.00,
        ]);

        // Manually reset cached balance to see if refresh fixes it
        $this->bankAccount->total_balance = 0.00;
        $this->bankAccount->save();

        $balance = $this->kernelService->refreshAccountBalance($this->bankAccount->id);
        $this->assertEquals(880000.00, $balance);

        $this->bankAccount->refresh();
        $this->assertEquals(880000.00, $this->bankAccount->total_balance);

        // Check net worth calculation
        $netWorth = $this->kernelService->calculateNetWorth();
        $this->assertEquals(1080000.00, $netWorth); // 880,000 + 200,000
    }
}
