<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\MerchantAlias;
use App\Models\Budget;
use App\Services\Finance\Kernel\FinancialKernelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerKernelIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $accountA;
    private Account $accountB;
    private Category $incomeCat;
    private Category $expenseCat;
    private Category $transferCat;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate user
        $this->user = User::factory()->create(['role' => 'user']);
        $this->actingAs($this->user);

        // Create accounts
        $this->accountA = Account::create([
            'user_id'        => $this->user->id,
            'name'           => 'BCA Account',
            'type'           => 'bank',
            'account_number' => '1234',
            'initial_balance'=> 1000.00,
            'total_balance'  => 1000.00,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        $this->accountB = Account::create([
            'user_id'        => $this->user->id,
            'name'           => 'Mandiri Account',
            'type'           => 'bank',
            'account_number' => '5678',
            'initial_balance'=> 500.00,
            'total_balance'  => 500.00,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        // Create categories
        $this->incomeCat = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'Salary',
            'type'    => 'income',
            'owner'   => 'afin',
        ]);

        $this->expenseCat = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'Dining',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);

        $this->transferCat = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'Transfer',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);
    }

    /**
     * Test creating an expense transaction.
     */
    public function test_create_expense(): void
    {
        $response = $this->post(route('transactions.store'), [
            'account_id'       => $this->accountA->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'items'            => [
                [
                    'category_id' => $this->expenseCat->id,
                    'amount'      => 150.00,
                    'description' => 'Dinner at Starbucks',
                ]
            ]
        ]);

        $response->assertRedirect(route('transactions.index'));
        
        $this->assertEquals(1, Transaction::count());
        $this->assertEquals(1, TransactionItem::count());

        $this->accountA->refresh();
        $this->assertEquals(850.00, $this->accountA->total_balance);
    }

    /**
     * Test creating an income transaction.
     */
    public function test_create_income(): void
    {
        $response = $this->post(route('transactions.store'), [
            'account_id'       => $this->accountA->id,
            'type'             => 'income',
            'transaction_date' => '2026-08-06',
            'items'            => [
                [
                    'category_id' => $this->incomeCat->id,
                    'amount'      => 200.00,
                    'description' => 'Freelance payout',
                ]
            ]
        ]);

        $response->assertRedirect(route('transactions.index'));

        $this->accountA->refresh();
        $this->assertEquals(1200.00, $this->accountA->total_balance);
    }

    /**
     * Test creating a transfer transaction.
     */
    public function test_create_transfer(): void
    {
        $response = $this->post(route('transactions.store'), [
            'account_id'       => $this->accountA->id,
            'to_account_id'    => $this->accountB->id,
            'type'             => 'transfer',
            'transaction_date' => '2026-08-06',
            'items'            => [
                [
                    'category_id' => $this->transferCat->id,
                    'amount'      => 300.00,
                    'description' => 'Internal transfer',
                ]
            ]
        ]);

        $response->assertRedirect(route('transactions.index'));

        $this->accountA->refresh();
        $this->accountB->refresh();

        $this->assertEquals(700.00, $this->accountA->total_balance);
        $this->assertEquals(800.00, $this->accountB->total_balance);
    }

    /**
     * Test revising/updating an existing transaction.
     */
    public function test_revise_transaction(): void
    {
        // 1. Pre-create transaction (Expense of 100 on Account A)
        $transaction = Transaction::create([
            'account_id'       => $this->accountA->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'total_amount'     => 100.00,
        ]);
        $transaction->transactionItems()->create([
            'category_id' => $this->expenseCat->id,
            'amount'      => 100.00,
            'description' => 'Initial item',
        ]);
        $this->accountA->refresh();
        $this->assertEquals(900.00, $this->accountA->total_balance);

        // 2. Revise transaction to be 300 on Account B
        $response = $this->put(route('transactions.update', $transaction->id), [
            'account_id'       => $this->accountB->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'items'            => [
                [
                    'category_id' => $this->expenseCat->id,
                    'amount'      => 300.00,
                    'description' => 'Revised item',
                ]
            ]
        ]);

        $response->assertRedirect(route('transactions.index'));

        // Account A balance must be restored back to 1000
        $this->accountA->refresh();
        $this->assertEquals(1000.00, $this->accountA->total_balance);

        // Account B balance must be decremented by 300 (500 - 300 = 200)
        $this->accountB->refresh();
        $this->assertEquals(200.00, $this->accountB->total_balance);
    }

    /**
     * Test removing a transaction.
     */
    public function test_remove_transaction(): void
    {
        $transaction = Transaction::create([
            'account_id'       => $this->accountA->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'total_amount'     => 150.00,
        ]);
        $transaction->transactionItems()->create([
            'category_id' => $this->expenseCat->id,
            'amount'      => 150.00,
        ]);
        $this->accountA->refresh();
        $this->assertEquals(850.00, $this->accountA->total_balance);

        $response = $this->delete(route('transactions.destroy', $transaction->id));
        $response->assertRedirect(route('transactions.index'));

        $this->assertEquals(0, Transaction::count());
        $this->accountA->refresh();
        $this->assertEquals(1000.00, $this->accountA->total_balance);
    }

    /**
     * Test transaction database rollback on kernel service failure.
     */
    public function test_rollback_behaviour(): void
    {
        $this->mock(FinancialKernelService::class, function ($mock) {
            $mock->shouldReceive('recordTransaction')
                ->andThrow(new \Exception('Mocked validation fail'));
        });

        $response = $this->post(route('transactions.store'), [
            'account_id'       => $this->accountA->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'items'            => [
                [
                    'category_id' => $this->expenseCat->id,
                    'amount'      => 150.00,
                    'description' => 'Dinner at Starbucks',
                ]
            ]
        ]);

        // Expect Exception to propagate, causing the response to fail/error out (or handled globally)
        $response->assertStatus(500);

        // Verify no transaction was recorded and balance remains unchanged
        $this->assertEquals(0, Transaction::count());
        $this->accountA->refresh();
        $this->assertEquals(1000.00, $this->accountA->total_balance);
    }

    /**
     * Test that inline category editing works.
     */
    public function test_inline_category_preserved(): void
    {
        $transaction = Transaction::create([
            'account_id'       => $this->accountA->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'total_amount'     => 150.00,
        ]);
        $item = $transaction->transactionItems()->create([
            'category_id' => $this->incomeCat->id, // initially marked as Salary (income category)
            'amount'      => 150.00,
        ]);

        $response = $this->post(route('transactions.inline-category', $transaction->id), [
            'category_id'    => $this->expenseCat->id,
            'subcategory_id' => null,
        ]);

        $response->assertJson([
            'success'     => true,
            'category_id' => $this->expenseCat->id,
        ]);

        $item->refresh();
        $this->assertEquals($this->expenseCat->id, $item->category_id);
    }

    /**
     * Test that merchant learning behaves correctly during category updates.
     */
    public function test_merchant_learning_preserved(): void
    {
        $transaction = Transaction::create([
            'account_id'       => $this->accountA->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'total_amount'     => 150.00,
            'raw_merchant'     => 'AMAZON CO',
            'merchant_key'     => 'amazon',
        ]);
        $item = $transaction->transactionItems()->create([
            'category_id' => $this->incomeCat->id,
            'amount'      => 150.00,
        ]);

        // Trigger inline category change
        $response = $this->post(route('transactions.inline-category', $transaction->id), [
            'category_id'    => $this->expenseCat->id,
            'subcategory_id' => null,
        ]);

        $response->assertJson(['success' => true]);

        // Verify MerchantAlias was updated or created
        $alias = MerchantAlias::where('merchant_key', 'amazon')->first();
        $this->assertNotNull($alias);
        $this->assertEquals($this->expenseCat->id, $alias->category_id);
    }

    /**
     * Test that budget calculations are not broken.
     */
    public function test_budget_calculations_unchanged(): void
    {
        // 1. Create a Budget
        $budget = Budget::create([
            'user_id'          => $this->user->id,
            'category_id'      => $this->expenseCat->id,
            'month'            => 8,
            'year'             => 2026,
            'allocated_amount' => 500.00,
            'owner'            => 'afin',
        ]);

        // 2. Create transaction for this category
        $this->post(route('transactions.store'), [
            'account_id'       => $this->accountA->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'items'            => [
                [
                    'category_id' => $this->expenseCat->id,
                    'amount'      => 100.00,
                    'description' => 'Dinner',
                ]
            ]
        ]);

        // 3. Request budgets list
        $response = $this->get(route('budgets.index', ['month' => 8, 'year' => 2026]));
        $response->assertStatus(200);
        $response->assertSee('100'); // verifies spent amount is visible
    }
}
