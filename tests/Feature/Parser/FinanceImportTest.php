<?php

namespace Tests\Feature\Parser;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\InboxMessage;
use App\Models\Transaction;
use App\Services\Finance\FinanceImporter;
use App\Services\Finance\Results\DuplicateResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceImportTest extends TestCase
{
    use RefreshDatabase;

    private FinanceImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = app(FinanceImporter::class);
    }

    /**
     * Test mapping priority: exact account number match.
     */
    public function test_import_maps_exact_account_number(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $account = Account::create([
            'user_id'        => $user->id,
            'name'           => 'BCA DEBIT',
            'type'           => 'bank',
            'account_number' => '0035',
            'total_balance'  => 0,
            'owner'          => 'afin',
        ]);

        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'msg_exact_acc_123',
            'subject'           => 'BCA Transfer',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "M-BCA:\nTANGGAL : 02/08/2026\nJAM : 14:30:15\nDARI REKENING : 1234567890\nKE REKENING : 0035\nNAMA PENGIRIM : JONATHAN DOE\nNOMINAL : Rp. 50.000,00\nKETERANGAN : Test\nNO. REFERENSI : TRF88291039",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $this->assertNotNull($result->transactionId);

        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals($account->id, $tx->account_id);
        $this->assertEquals(50000, $tx->total_amount);

        // Verify status transitions
        $message->refresh();
        $this->assertEquals(InboxMessage::STATUS_IMPORTED, $message->status);
        $this->assertEquals($tx->id, $message->transaction_id);
        $this->assertNotNull($message->imported_at);

        // Verify transaction items
        $this->assertCount(1, $tx->transactionItems);
        $item = $tx->transactionItems->first();
        $this->assertEquals(50000, $item->amount);
        $this->assertEquals('Uncategorized', $item->category->name);
    }

    /**
     * Test mapping priority: suffix account number match.
     */
    public function test_import_maps_suffix_account_number(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $account = Account::create([
            'user_id'        => $user->id,
            'name'           => 'MANDIRI PAYROLL',
            'type'           => 'bank',
            'account_number' => '5882',
            'total_balance'  => 0,
            'owner'          => 'afin',
        ]);

        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'msg_suffix_acc_456',
            'subject'           => 'BCA Transfer Masuk',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "M-BCA:\nTANGGAL : 02/08/2026\nJAM : 14:30:15\nDARI REKENING : 1234567890\nKE REKENING : xxxx5882\nNAMA PENGIRIM : JONATHAN DOE\nNOMINAL : Rp. 150.000,00\nKETERANGAN : Test\nNO. REFERENSI : TRF88291039",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals($account->id, $tx->account_id);
        $this->assertEquals(150000, $tx->total_amount);
    }

    /**
     * Test mapping priority: bank fallback match.
     */
    public function test_import_falls_back_to_bank_name(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $account = Account::create([
            'user_id'        => $user->id,
            'name'           => 'BCA DEBIT',
            'type'           => 'bank',
            'account_number' => null,
            'total_balance'  => 0,
            'owner'          => 'afin',
        ]);

        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'msg_fallback_acc_789',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 02/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 75.000,00\nNO. REFERENSI : DBT99882211",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals($account->id, $tx->account_id);
        $this->assertEquals(75000, $tx->total_amount);
    }

    /**
     * Test duplicate transaction detection.
     */
    public function test_import_ignores_duplicates(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $account = Account::create([
            'user_id'        => $user->id,
            'name'           => 'BCA DEBIT',
            'type'           => 'bank',
            'account_number' => '0035',
            'total_balance'  => 0,
            'owner'          => 'afin',
        ]);

        // Pre-create transaction in database with external_id
        $tx = Transaction::create([
            'external_id'      => 'google_msg_dup_999',
            'account_id'       => $account->id,
            'type'             => 'expense',
            'transaction_date' => '2026-08-02',
            'total_amount'     => 100000,
            'notes'            => 'Pre-existing transaction',
        ]);

        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'google_msg_dup_999',
            'subject'           => 'BCA Debit',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 02/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 100.000,00\nNO. REFERENSI : DBT99882211",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertInstanceOf(DuplicateResult::class, $result);
        $this->assertEquals('DUPLICATE', $result->status);
        $this->assertEquals($tx->id, $result->transactionId);

        // Verify status transitions on message
        $message->refresh();
        $this->assertEquals(InboxMessage::STATUS_IMPORTED, $message->status);
        $this->assertEquals($tx->id, $message->transaction_id);
        $this->assertTrue($message->raw_payload['is_duplicate']);
    }

    /**
     * Test double-sided account mapping for transfers.
     */
    public function test_import_handles_transfers_between_user_accounts(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $source = Account::create([
            'user_id'        => $user->id,
            'name'           => 'BCA DEBIT',
            'type'           => 'bank',
            'account_number' => '0035',
            'total_balance'  => 500000,
            'owner'          => 'afin',
        ]);

        $destination = Account::create([
            'user_id'        => $user->id,
            'name'           => 'MANDIRI PAYROLL',
            'type'           => 'bank',
            'account_number' => '5882',
            'total_balance'  => 0,
            'owner'          => 'afin',
        ]);

        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'msg_transfer_between_accs',
            'subject'           => 'BCA Transfer',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "M-BCA:\nTANGGAL : 02/08/2026\nJAM : 14:30:15\nDARI REKENING : 0035\nKE REKENING : 5882\nNAMA PENERIMA : SELF\nNOMINAL : Rp. 200.000,00\nKETERANGAN : Test Transfer\nNO. REFERENSI : TRF88291039",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $tx = Transaction::findOrFail($result->transactionId);

        $this->assertEquals($source->id, $tx->account_id);
        $this->assertEquals($destination->id, $tx->to_account_id);
        $this->assertEquals('transfer', $tx->type);
        $this->assertEquals(200000, $tx->total_amount);
    }

    /**
     * Test read-only internal transfer detection metadata.
     */
    public function test_import_marks_internal_transfer_candidates_in_metadata(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $source = Account::create([
            'user_id'        => $user->id,
            'name'           => 'BCA MAIN',
            'type'           => 'bank',
            'account_number' => '0035',
            'total_balance'  => 500000,
            'owner'          => 'Afin',
        ]);

        $destination = Account::create([
            'user_id'        => $user->id,
            'name'           => 'MANDIRI SAVINGS',
            'type'           => 'bank',
            'account_number' => '5882',
            'total_balance'  => 0,
            'owner'          => 'Afin',
        ]);

        $message = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'msg_internal_candidate',
            'subject'           => 'BCA Transfer',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "M-BCA:\nTANGGAL : 02/08/2026\nJAM : 14:30:15\nDARI REKENING : 0035\nKE REKENING : 5882\nNAMA PENERIMA : Afin\nNOMINAL : Rp. 200.000,00\nKETERANGAN : Internal Move\nNO. REFERENSI : TRF88112233",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);

        $message->refresh();
        $this->assertTrue($message->raw_payload['internal_transfer_candidate'] ?? false);

        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals('transfer', $tx->type);
        $this->assertEquals($source->id, $tx->account_id);
        $this->assertEquals($destination->id, $tx->to_account_id);
    }

    /**
     * Test controller bulk action endpoints.
     */
    public function test_controller_import_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $account = Account::create([
            'user_id'        => $user->id,
            'name'           => 'BCA DEBIT',
            'type'           => 'bank',
            'account_number' => '0035',
            'total_balance'  => 0,
            'owner'          => 'afin',
        ]);

        // Create a READY message
        $readyMsg = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_READY,
            'google_message_id' => 'msg_ready_1',
            'subject'           => 'BCA Transfer',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "M-BCA:\nTANGGAL : 02/08/2026\nJAM : 14:30:15\nDARI REKENING : 1234567890\nKE REKENING : 0035\nNAMA PENGIRIM : JONATHAN DOE\nNOMINAL : Rp. 10.000,00\nKETERANGAN : Test\nNO. REFERENSI : TRF88291039",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        // Create a NEW message
        $newMsg = InboxMessage::create([
            'user_id'           => $user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'msg_new_1',
            'subject'           => 'BCA Transfer',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "M-BCA:\nTANGGAL : 02/08/2026\nJAM : 14:30:15\nDARI REKENING : 1234567890\nKE REKENING : 0035\nNAMA PENGIRIM : JONATHAN DOE\nNOMINAL : Rp. 20.000,00\nKETERANGAN : Test\nNO. REFERENSI : TRF88291040",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        // Test POST /gmail-inbox/parse
        $response = $this->post(route('gmail-inbox.parse'));
        $response->assertRedirect(route('gmail-inbox.index'));
        $response->assertSessionHas('success');

        $newMsg->refresh();
        $this->assertEquals(InboxMessage::STATUS_READY, $newMsg->status);

        // Test POST /gmail-inbox/import-selected
        $response = $this->post(route('gmail-inbox.import-selected'), [
            'selected_messages' => [$readyMsg->id]
        ]);
        $response->assertRedirect(route('gmail-inbox.index'));
        $response->assertSessionHas('success');

        $readyMsg->refresh();
        $this->assertEquals(InboxMessage::STATUS_IMPORTED, $readyMsg->status);
        $this->assertNotNull($readyMsg->transaction_id);

        // Test POST /gmail-inbox/import-all-ready
        $response = $this->post(route('gmail-inbox.import-all-ready'));
        $response->assertRedirect(route('gmail-inbox.index'));
        $response->assertSessionHas('success');

        $newMsg->refresh();
        $this->assertEquals(InboxMessage::STATUS_IMPORTED, $newMsg->status);
        $this->assertNotNull($newMsg->transaction_id);
    }
}
