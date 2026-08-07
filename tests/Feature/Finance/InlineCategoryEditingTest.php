<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\MerchantAlias;
use App\Models\InboxMessage;
use App\Services\Finance\FinanceImporter;
use App\Services\Finance\TransactionCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InlineCategoryEditingTest extends TestCase
{
    use RefreshDatabase;

    private TransactionCategoryService $categoryService;
    private FinanceImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryService = app(TransactionCategoryService::class);
        $this->importer = app(FinanceImporter::class);
    }

    public function test_service_updates_category_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Food',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'account_number' => '0000',
            'total_balance' => 50000,
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => 'expense',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 10000,
        ]);

        $item = $transaction->transactionItems()->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'description' => 'Test',
        ]);

        $newCategory = Category::create([
            'user_id' => $user->id,
            'name' => 'Bills',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $newCategory->id,
            'name' => 'Electricity',
            'is_active' => true,
        ]);

        $this->categoryService->updateCategoryAndSubcategory($item, $newCategory->id, $subcategory->id);

        $item->refresh();
        $this->assertEquals($newCategory->id, $item->category_id);
        $this->assertEquals($subcategory->id, $item->subcategory_id);
    }

    public function test_service_fails_when_category_is_inactive(): void
    {
        $user = User::factory()->create();

        $inactiveCategory = Category::create([
            'user_id' => $user->id,
            'name' => 'Inactive Cat',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => false,
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'account_number' => '0000',
            'total_balance' => 50000,
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => 'expense',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 10000,
        ]);

        $item = $transaction->transactionItems()->create([
            'category_id' => $inactiveCategory->id,
            'amount' => 10000,
        ]);

        $this->expectException(ValidationException::class);
        $this->categoryService->updateCategoryAndSubcategory($item, $inactiveCategory->id, null);
    }

    public function test_service_fails_when_subcategory_belongs_to_other_category(): void
    {
        $user = User::factory()->create();

        $catA = Category::create([
            'user_id' => $user->id,
            'name' => 'Cat A',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $catB = Category::create([
            'user_id' => $user->id,
            'name' => 'Cat B',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $subOfA = Subcategory::create([
            'category_id' => $catA->id,
            'name' => 'Sub of A',
            'is_active' => true,
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'account_number' => '0000',
            'total_balance' => 50000,
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => 'expense',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 10000,
        ]);

        $item = $transaction->transactionItems()->create([
            'category_id' => $catA->id,
            'amount' => 10000,
        ]);

        // Attempting to assign subOfA under catB should fail
        $this->expectException(ValidationException::class);
        $this->categoryService->updateCategoryAndSubcategory($item, $catB->id, $subOfA->id);
    }

    public function test_inline_category_endpoint(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Food',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'account_number' => '0000',
            'total_balance' => 50000,
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => 'expense',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 10000,
        ]);

        $item = $transaction->transactionItems()->create([
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $newCategory = Category::create([
            'user_id' => $user->id,
            'name' => 'Bills',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $response = $this->postJson(route('transactions.inline-category', $transaction), [
            'category_id' => $newCategory->id,
            'subcategory_id' => null,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'category_id' => $newCategory->id,
            'subcategory_id' => null,
        ]);

        $item->refresh();
        $this->assertEquals($newCategory->id, $item->category_id);
    }

    public function test_merchant_learning_via_inline_category(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Food',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'account_number' => '1234',
            'total_balance' => 100000,
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => 'expense',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 35000,
            'merchant_key' => 'warungmanado',
            'raw_merchant' => 'WARUNG MANADO',
        ]);

        $item = $transaction->transactionItems()->create([
            'category_id' => $category->id,
            'amount' => 35000,
        ]);

        $alias = MerchantAlias::create([
            'merchant_key' => 'warungmanado',
            'raw_merchant' => 'WARUNG MANADO',
            'display_name' => 'WARUNG MANADO',
            'usage_count' => 1,
            'is_locked' => false,
        ]);

        $newCategory = Category::create([
            'user_id' => $user->id,
            'name' => 'Dining Out',
            'type' => 'expense',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $newCategory->id,
            'name' => 'Restaurants',
            'is_active' => true,
        ]);

        $response = $this->postJson(route('transactions.inline-category', $transaction), [
            'category_id' => $newCategory->id,
            'subcategory_id' => $subcategory->id,
        ]);

        $response->assertStatus(200);

        $alias->refresh();
        $this->assertEquals($newCategory->id, $alias->category_id);
        $this->assertEquals($subcategory->id, $alias->subcategory_id);
        $this->assertTrue($alias->is_locked);
    }
}
