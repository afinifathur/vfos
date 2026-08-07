<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\InboxMessage;
use App\Models\Transaction;
use App\Models\MerchantAlias;
use App\Services\Finance\FinanceImporter;
use App\Services\Finance\Kernel\FinancialKernelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceImporterKernelIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private FinanceImporter $importer;
    private User $user;
    private Account $bankAccount;
    private Account $secondaryAccount;
    private Category $incomeCategory;
    private Category $expenseCategory;
    private Category $transferCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->importer = app(FinanceImporter::class);

        // Create and authenticate user
        $this->user = User::factory()->create(['role' => 'user']);
        $this->actingAs($this->user);

        // Create accounts - Ensure name aligns with fallback checks
        $this->bankAccount = Account::create([
            'user_id'        => $this->user->id,
            'name'           => 'BCA DEBIT',
            'type'           => 'bank',
            'account_number' => '1234',
            'initial_balance'=> 1000000.00,
            'total_balance'  => 1000000.00,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        $this->secondaryAccount = Account::create([
            'user_id'        => $this->user->id,
            'name'           => 'MANDIRI PAYROLL',
            'type'           => 'bank',
            'account_number' => '5678',
            'initial_balance'=> 500000.00,
            'total_balance'  => 500000.00,
            'owner'          => 'afin',
            'is_active'      => true,
        ]);

        // Create categories
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
     * Test importing an income transaction.
     */
    public function test_import_income(): void
    {
        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'income-msg-1',
            'subject'           => 'BCA Transfer Masuk',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "M-BCA:\nTANGGAL : 06/08/2026\nJAM : 14:30:15\nDARI REKENING : 9999\nKE REKENING : 1234\nNAMA PENGIRIM : PT MAJU MUNDUR\nNOMINAL : Rp. 100.000,00\nKETERANGAN : Test\nNO. REFERENSI : TRF88291039",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $this->assertNotNull($result->transactionId);

        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals('income', $tx->type);
        $this->assertEquals(100000.00, $tx->total_amount);
        $this->assertEquals($this->bankAccount->id, $tx->account_id);

        $this->bankAccount->refresh();
        $this->assertEquals(1100000.00, $this->bankAccount->total_balance);
    }

    /**
     * Test importing an expense transaction.
     */
    public function test_import_expense(): void
    {
        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'expense-msg-1',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);

        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals('expense', $tx->type);
        $this->assertEquals(50000.00, $tx->total_amount);

        $this->bankAccount->refresh();
        $this->assertEquals(950000.00, $this->bankAccount->total_balance);
    }

    /**
     * Test importing an internal transfer between user accounts.
     */
    public function test_import_internal_transfer(): void
    {
        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'transfer-msg-1',
            'subject'           => 'BCA Transfer',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "M-BCA:\nTANGGAL : 06/08/2026\nJAM : 14:30:15\nDARI REKENING : 1234\nKE REKENING : 5678\nNAMA PENERIMA : SELF\nNOMINAL : Rp. 200.000,00\nKETERANGAN : Test Transfer\nNO. REFERENSI : TRF88291039",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);

        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals('transfer', $tx->type);
        $this->assertEquals(200000.00, $tx->total_amount);
        $this->assertEquals($this->bankAccount->id, $tx->account_id);
        $this->assertEquals($this->secondaryAccount->id, $tx->to_account_id);

        $this->bankAccount->refresh();
        $this->secondaryAccount->refresh();

        $this->assertEquals(800000.00, $this->bankAccount->total_balance);
        $this->assertEquals(700000.00, $this->secondaryAccount->total_balance);
    }

    /**
     * Test duplicate message detection.
     */
    public function test_import_duplicate_detection(): void
    {
        // Pre-create the transaction in database to simulate duplicate
        $tx = Transaction::create([
            'external_id'      => 'dup-msg-1',
            'account_id'       => $this->bankAccount->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-06',
            'total_amount'     => 50000.00,
            'notes'            => 'Pre-existing duplicate',
        ]);

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'dup-msg-1',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $res = $this->importer->import($message);
        
        $this->assertEquals('DUPLICATE', $res->status);
        $this->assertEquals($tx->id, $res->transactionId);
    }

    /**
     * Test merchant and category learning logic.
     */
    public function test_merchant_learning_association(): void
    {
        // Pre-seed locked merchant mapping
        MerchantAlias::create([
            'merchant_key'   => 'starbucks',
            'raw_merchant'   => 'STARBUCKS',
            'display_name'   => 'STARBUCKS',
            'category_id'    => $this->expenseCategory->id,
            'usage_count'    => 1,
            'is_locked'      => true,
        ]);

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'merchant-msg-1',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);
        $this->assertEquals('SUCCESS', $result->status);

        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals('starbucks', $tx->merchant_key);

        $item = $tx->transactionItems()->first();
        $this->assertEquals($this->expenseCategory->id, $item->category_id);
    }

    /**
     * Test transaction database rollback on error.
     */
    public function test_import_rollback_behaviour(): void
    {
        // Mock the FinancialKernelService to throw an exception when createExpense is called
        $this->mock(FinancialKernelService::class, function ($mock) {
            $mock->shouldReceive('createExpense')
                ->andThrow(new \Exception('Mocked DB failure'));
        });

        // Resolve importer again to inject the mocked service
        $importer = app(FinanceImporter::class);

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'rollback-msg-1',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $importer->import($message);

        $this->assertEquals('FAILED', $result->status);
        $this->assertStringContainsString('Database transaction failed: Mocked DB failure', $result->message);

        // Verify no transactions were created
        $this->assertEquals(0, Transaction::count());
        // Verify message status is FAILED
        $message->refresh();
        $this->assertEquals(InboxMessage::STATUS_FAILED, $message->status);
    }
}
