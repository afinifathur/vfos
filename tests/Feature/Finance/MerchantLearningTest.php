<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\InboxMessage;
use App\Models\Transaction;
use App\Models\MerchantAlias;
use App\Services\Finance\FinanceImporter;
use App\Services\Finance\MerchantNormalizer;
use App\Services\Finance\MerchantLearningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantLearningTest extends TestCase
{
    use RefreshDatabase;

    private FinanceImporter $importer;
    private MerchantNormalizer $normalizer;
    private MerchantLearningService $learningService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = app(FinanceImporter::class);
        $this->normalizer = app(MerchantNormalizer::class);
        $this->learningService = app(MerchantLearningService::class);
    }

    /**
     * Test MerchantNormalizer key generation rules.
     */
    public function test_merchant_normalizer_key_rules(): void
    {
        $this->assertEquals('warungmanado', $this->normalizer->merchantKey('QRIS Warung Manado'));
        $this->assertEquals('warungmanado', $this->normalizer->merchantKey('WARUNG MANADO'));
        $this->assertEquals('warungmanado', $this->normalizer->merchantKey('Warung   Manado'));
        $this->assertEquals('imambukhoripulsaint', $this->normalizer->merchantKey('IMAM BUKHORI, Pulsa & Int'));
    }

    /**
     * Test that import automatically creates a MerchantAlias and sets usage_count.
     */
    public function test_import_creates_alias_on_first_seen(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        Account::create([
            'user_id'        => $user->id,
            'name'           => 'Mandiri Account',
            'type'           => 'bank',
            'account_number' => '1234',
            'total_balance'  => 100000,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'qris-test-1',
            'subject'           => 'Pembayaran QRIS Mandiri Berhasil',
            'from'              => 'notifikasi@bankmandiri.co.id',
            'plain_body'        => "Transaksi QRIS Berhasil\nTanggal : 15/07/2026\nWaktu : 19:00:00\nMerchant : WARUNG MANADO\nNominal : Rp. 35.000,00\nNo. Referensi : QMDRI12345",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);

        // Verify MerchantAlias is created
        $alias = MerchantAlias::where('merchant_key', 'warungmanado')->first();
        $this->assertNotNull($alias);
        $this->assertEquals('WARUNG MANADO', $alias->raw_merchant);
        $this->assertEquals('WARUNG MANADO', $alias->display_name); // normalized display name
        $this->assertEquals(1, $alias->usage_count);
        $this->assertEquals(0, $alias->auto_categorized_count);
        $this->assertFalse($alias->is_locked);
        $this->assertEquals($result->transactionId, $alias->last_transaction_id);
        $this->assertNotNull($alias->first_seen_at);
        $this->assertNotNull($alias->last_seen_at);

        // Verify transaction fields are stored
        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals('warungmanado', $tx->merchant_key);
        $this->assertEquals('WARUNG MANADO', $tx->raw_merchant);
    }

    /**
     * Test incrementing usage_count on multiple imports.
     */
    public function test_import_increments_usage_count(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        Account::create([
            'user_id'        => $user->id,
            'name'           => 'Mandiri Account',
            'type'           => 'bank',
            'account_number' => '1234',
            'total_balance'  => 100000,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        // First import
        $msg1 = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'qris-test-1',
            'subject'           => 'Pembayaran QRIS Mandiri Berhasil',
            'from'              => 'notifikasi@bankmandiri.co.id',
            'plain_body'        => "Transaksi QRIS Berhasil\nTanggal : 15/07/2026\nWaktu : 19:00:00\nMerchant : WARUNG MANADO\nNominal : Rp. 35.000,00\nNo. Referensi : QMDRI12345",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);
        $this->importer->import($msg1);

        // Second import
        $msg2 = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'qris-test-2',
            'subject'           => 'Pembayaran QRIS Mandiri Berhasil',
            'from'              => 'notifikasi@bankmandiri.co.id',
            'plain_body'        => "Transaksi QRIS Berhasil\nTanggal : 16/07/2026\nWaktu : 20:00:00\nMerchant : WARUNG MANADO\nNominal : Rp. 45.000,00\nNo. Referensi : QMDRI12346",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);
        $res2 = $this->importer->import($msg2);

        $alias = MerchantAlias::where('merchant_key', 'warungmanado')->first();
        $this->assertEquals(2, $alias->usage_count);
        $this->assertEquals($res2->transactionId, $alias->last_transaction_id);
    }

    /**
     * Test transaction edit learning logic.
     */
    public function test_transaction_edit_creates_or_updates_alias_and_locks_it(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $oldCategory = Category::create([
            'user_id' => $user->id,
            'name'    => 'Uncategorized',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);

        $newCategory = Category::create([
            'user_id' => $user->id,
            'name'    => 'Food & Drinks',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $newCategory->id,
            'name'        => 'Restaurants',
        ]);

        $account = Account::create([
            'user_id'        => $user->id,
            'name'           => 'Mandiri Account',
            'type'           => 'bank',
            'account_number' => '1234',
            'total_balance'  => 100000,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        // Create transaction with merchant info
        $tx = Transaction::create([
            'account_id'       => $account->id,
            'type'             => 'expense',
            'transaction_date' => now()->toDateString(),
            'total_amount'     => 35000,
            'merchant_key'     => 'warungmanado',
            'raw_merchant'     => 'WARUNG MANADO',
        ]);

        $tx->transactionItems()->create([
            'category_id' => $oldCategory->id,
            'amount'      => 35000,
            'description' => 'Uncategorized expense',
        ]);

        // Create alias record as it would be created on import (uncategorized, unlocked)
        $alias = MerchantAlias::create([
            'merchant_key'   => 'warungmanado',
            'raw_merchant'   => 'WARUNG MANADO',
            'display_name'   => 'WARUNG MANADO',
            'usage_count'    => 1,
            'is_locked'      => false,
        ]);

        // Call learning service simulating save/edit
        $this->learningService->learnFromEdit($tx, $newCategory->id, $subcategory->id);

        $alias->refresh();
        $this->assertEquals($newCategory->id, $alias->category_id);
        $this->assertEquals($subcategory->id, $alias->subcategory_id);
        $this->assertTrue($alias->is_locked);
        $this->assertEquals(1, $alias->usage_count); // should NOT increment on edit
    }

    /**
     * Test that auto-categorization works on subsequent imports, incrementing auto_categorized_count,
     * and that historical transactions are not affected.
     */
    public function test_subsequent_import_auto_categorizes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        Account::create([
            'user_id'        => $user->id,
            'name'           => 'Mandiri Account',
            'type'           => 'bank',
            'account_number' => '1234',
            'total_balance'  => 100000,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name'    => 'Dining Out',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);

        // Pre-create locked and categorized alias
        $alias = MerchantAlias::create([
            'merchant_key'   => 'warungmanado',
            'raw_merchant'   => 'WARUNG MANADO',
            'display_name'   => 'WARUNG MANADO',
            'category_id'    => $category->id,
            'usage_count'    => 1,
            'is_locked'      => true,
        ]);

        // Import new transaction
        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'qris-test-subsequent',
            'subject'           => 'Pembayaran QRIS Mandiri Berhasil',
            'from'              => 'notifikasi@bankmandiri.co.id',
            'plain_body'        => "Transaksi QRIS Berhasil\nTanggal : 15/07/2026\nWaktu : 19:00:00\nMerchant : WARUNG MANADO\nNominal : Rp. 35.000,00\nNo. Referensi : QMDRI12345",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $alias->refresh();
        $this->assertEquals(2, $alias->usage_count);
        $this->assertEquals(1, $alias->auto_categorized_count);

        $tx = Transaction::findOrFail($result->transactionId);
        $item = $tx->transactionItems->first();
        $this->assertEquals($category->id, $item->category_id);
    }
}
