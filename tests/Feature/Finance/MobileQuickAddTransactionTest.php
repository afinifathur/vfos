<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileQuickAddTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $cashAccount;
    private Account $bankAccount;
    private Category $transportCategory;
    private Subcategory $parkingSubcategory;
    private Category $salaryCategory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate user
        $this->user = User::factory()->create(['role' => 'user']);
        $this->actingAs($this->user);

        // Create Cash account
        $this->cashAccount = Account::create([
            'user_id'         => $this->user->id,
            'name'            => 'Cash Dompet',
            'type'            => 'cash',
            'account_number'  => null,
            'initial_balance' => 500000.00,
            'total_balance'   => 500000.00,
            'owner'           => 'afin',
            'is_active'       => true,
        ]);

        // Create Bank account
        $this->bankAccount = Account::create([
            'user_id'         => $this->user->id,
            'name'            => 'BCA Debit',
            'type'            => 'bank',
            'account_number'  => '12345678',
            'initial_balance' => 1000000.00,
            'total_balance'   => 1000000.00,
            'owner'           => 'afin',
            'is_active'       => true,
        ]);

        // Create Expense Category and Subcategory
        $this->transportCategory = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'Transportasi',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);

        $this->parkingSubcategory = Subcategory::create([
            'category_id' => $this->transportCategory->id,
            'name'        => 'Parkir',
        ]);

        // Create Income Category
        $this->salaryCategory = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'Gaji',
            'type'    => 'income',
            'owner'   => 'afin',
        ]);
    }

    /**
     * 1. Test Quick Add Expense with Cash account.
     */
    public function test_quick_add_expense_with_cash_account(): void
    {
        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id'    => $this->transportCategory->id,
                    'subcategory_id' => $this->parkingSubcategory->id,
                    'description'    => 'Parkir Mall',
                    'amount'         => 15000.00,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Transaction recorded successfully.',
            'data'    => [
                'amount' => 15000.00,
                'type'   => 'expense',
            ]
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id'   => $this->cashAccount->id,
            'type'         => 'expense',
            'total_amount' => 15000.00,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'category_id'    => $this->transportCategory->id,
            'subcategory_id' => $this->parkingSubcategory->id,
            'description'    => 'Parkir Mall',
            'amount'         => 15000.00,
        ]);

        $this->cashAccount->refresh();
        $this->assertEquals(485000.00, $this->cashAccount->total_balance);
    }

    /**
     * 2. Test Quick Add Income with Cash account.
     */
    public function test_quick_add_income_with_cash_account(): void
    {
        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'income',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id'    => $this->salaryCategory->id,
                    'subcategory_id' => null,
                    'description'    => 'Uang Saku',
                    'amount'         => 50000.00,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'amount' => 50000.00,
                'type'   => 'income',
            ]
        ]);

        $this->cashAccount->refresh();
        $this->assertEquals(550000.00, $this->cashAccount->total_balance);
    }

    /**
     * 3. Test Quick Add Expense with another account (e.g. BCA Bank).
     */
    public function test_quick_add_expense_with_another_account(): void
    {
        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->bankAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id'    => $this->transportCategory->id,
                    'subcategory_id' => null,
                    'description'    => 'Bensin Pertamax',
                    'amount'         => 100000.00,
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->bankAccount->refresh();
        $this->assertEquals(900000.00, $this->bankAccount->total_balance);
    }

    /**
     * 4. Test Cash default resolution in create view when Cash account exists.
     */
    public function test_default_cash_account_resolution_when_present(): void
    {
        $response = $this->get(route('transactions.create'));

        $response->assertStatus(200);
        $response->assertViewHas('defaultAccountId', $this->cashAccount->id);
    }

    /**
     * 5. Test Cash default resolution when NO Cash account exists.
     */
    public function test_default_cash_account_resolution_when_missing(): void
    {
        // Deactivate cash account
        $this->cashAccount->update(['is_active' => false]);

        $response = $this->get(route('transactions.create'));

        $response->assertStatus(200);
        // defaultAccountId should be null so no arbitrary account is selected silently
        $response->assertViewHas('defaultAccountId', null);
    }

    /**
     * 6. Test Category and Subcategory dependency.
     */
    public function test_category_and_subcategory_dependency(): void
    {
        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id'    => $this->transportCategory->id,
                    'subcategory_id' => $this->parkingSubcategory->id,
                    'description'    => 'Parkir Kantor',
                    'amount'         => 5000.00,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('transaction_items', [
            'category_id'    => $this->transportCategory->id,
            'subcategory_id' => $this->parkingSubcategory->id,
        ]);
    }

    /**
     * 7. Test validation failure on invalid amount.
     */
    public function test_validation_fails_on_invalid_amount(): void
    {
        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id' => $this->transportCategory->id,
                    'amount'      => -500.00, // Invalid negative amount
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.amount']);
    }

    /**
     * 8. Test validation failure on missing category.
     */
    public function test_validation_fails_on_missing_category(): void
    {
        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'amount'      => 10000.00,
                    'description' => 'Makan',
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.category_id']);
    }

    /**
     * 9. Test repeated transaction entry (multiple successive quick adds).
     */
    public function test_repeated_transaction_entry(): void
    {
        // Entry 1: Parkir 5k
        $res1 = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id' => $this->transportCategory->id,
                    'amount'      => 5000.00,
                    'description' => 'Parkir 1',
                ]
            ]
        ]);
        $res1->assertStatus(200);

        // Entry 2: Snack 10k
        $res2 = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id' => $this->transportCategory->id,
                    'amount'      => 10000.00,
                    'description' => 'Snack 2',
                ]
            ]
        ]);
        $res2->assertStatus(200);

        $this->assertEquals(2, Transaction::count());
        $this->cashAccount->refresh();
        $this->assertEquals(485000.00, $this->cashAccount->total_balance);
    }

    /**
     * 10. Test desktop transaction creation continues to redirect to index.
     */
    public function test_desktop_transaction_creation_redirects(): void
    {
        $response = $this->post(route('transactions.store'), [
            'account_id'       => $this->bankAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id' => $this->transportCategory->id,
                    'amount'      => 20000.00,
                    'description' => 'Desktop Entry',
                ]
            ]
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Transaction recorded successfully.');
    }

    /**
     * 11. Test that mismatched subcategory (belonging to another category) is rejected.
     */
    public function test_mismatched_subcategory_is_rejected(): void
    {
        $otherCategory = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'Makanan',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);

        $otherSub = Subcategory::create([
            'category_id' => $otherCategory->id,
            'name'        => 'Restoran',
        ]);

        // Attempt to submit Transportasi with Restoran (Makanan's subcategory)
        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id'    => $this->transportCategory->id,
                    'subcategory_id' => $otherSub->id,
                    'description'    => 'Mismatch test',
                    'amount'         => 15000.00,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.subcategory_id']);
    }

    /**
     * 12. Test that category belonging to another owner scope is rejected (404).
     */
    public function test_category_from_another_owner_scope_is_rejected(): void
    {
        $foreignCategory = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'Pacar Expense',
            'type'    => 'expense',
            'owner'   => 'pacar',
        ]);

        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id' => $foreignCategory->id,
                    'amount'      => 15000.00,
                    'description' => 'Foreign category',
                ]
            ]
        ]);

        // HasOwner global scope causes Category::findOrFail to fail with 404
        $response->assertStatus(404);
    }

    /**
     * 13. Test that Mobile Quick Add HTML attributes avoid Safari pattern mismatch.
     * Checks inputmode="numeric", no pattern="[0-9]*", and novalidate on form.
     */
    public function test_mobile_quick_add_html_attributes_avoid_safari_pattern_mismatch(): void
    {
        $response = $this->get(route('transactions.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Ensure mobile_amount_display uses inputmode="numeric"
        $this->assertStringContainsString('id="mobile_amount_display"', $content);
        $this->assertStringContainsString('inputmode="numeric"', $content);

        // Ensure conflicting pattern="[0-9]*" is NOT present on mobile_amount_display
        $this->assertStringNotContainsString('pattern="[0-9]*"', $content);
        $this->assertStringNotContainsString('pattern=', $content);

        // Ensure form uses novalidate to prevent native constraint validation bubble
        $this->assertStringContainsString('<form id="mobile_quick_add_form"', $content);
        $this->assertStringContainsString('novalidate', $content);
    }

    /**
     * 14. Test quick add with various nominal amounts (7.000, 15.000, 1.500.000).
     */
    public function test_quick_add_various_nominal_amounts(): void
    {
        $amounts = [7000.00, 15000.00, 1500000.00];

        foreach ($amounts as $amount) {
            $response = $this->postJson(route('transactions.store'), [
                'account_id'       => $this->cashAccount->id,
                'type'             => 'expense',
                'transaction_date' => now()->toDateString(),
                'items'            => [
                    [
                        'category_id' => $this->transportCategory->id,
                        'amount'      => $amount,
                        'description' => "Test amount {$amount}",
                    ]
                ]
            ]);

            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'data'    => [
                    'amount' => $amount,
                    'type'   => 'expense',
                ]
            ]);
        }
    }

    /**
     * 15. Test validation fails when amount is empty or non-numeric.
     */
    public function test_validation_fails_on_empty_amount(): void
    {
        $response = $this->postJson(route('transactions.store'), [
            'account_id'       => $this->cashAccount->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'items'            => [
                [
                    'category_id' => $this->transportCategory->id,
                    'amount'      => '',
                    'description' => 'Empty amount',
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.amount']);
    }
}

