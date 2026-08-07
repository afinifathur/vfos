<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\InboxMessage;
use App\Models\Transaction;
use App\Models\AccountIdentifier;
use App\Services\Finance\FinanceImporter;
use App\Services\Finance\Identity\AccountIdentityResolverInterface;
use App\Services\Finance\Identity\IdentityResolutionStatistics;
use App\Services\Finance\Identity\ImportResolutionTrace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class FinanceImporterIdentityMigrationTest extends TestCase
{
    use RefreshDatabase;

    private FinanceImporter $importer;
    private AccountIdentityResolverInterface $resolver;
    private User $user;
    private Account $bankAccount;
    private Account $secondaryAccount;
    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->importer = app(FinanceImporter::class);
        $this->resolver = app(AccountIdentityResolverInterface::class);

        // Create and authenticate user
        $this->user = User::factory()->create(['role' => 'user']);
        $this->actingAs($this->user);

        // Create accounts
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

        // Create category
        $this->expenseCategory = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'makan',
            'type'    => 'expense',
            'owner'   => 'afin',
        ]);
    }

    /**
     * Test 1: Resolver exact match when flag is ON.
     */
    public function test_resolver_exact_match_when_flag_on(): void
    {
        Config::set('finance.use_identity_resolver', true);
        IdentityResolutionStatistics::reset();

        // Register exact account number identifier for secondary account
        $this->resolver->registerIdentifier(
            accountId: $this->secondaryAccount->id,
            type: 'account_number',
            value: '5678',
            provider: 'MANDIRI',
            priority: 100,
            source: 'manual'
        );

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'resolver-msg-1',
            'subject'           => 'Mandiri Debit Card',
            'from'              => 'mandiri@mandiri.co.id',
            'plain_body'        => "Tanggal : 06/08/2026\nJam : 12:00:00\nNominal : Rp. 50.000,00\nNo. Referensi : DBT99882211\nRekening Asal : 5678",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals($this->secondaryAccount->id, $tx->account_id);
        
        // Stat verification
        $this->assertEquals(1, IdentityResolutionStatistics::get('exact_match'));
        
        // Trace level verification (should be INFO since it resolved successfully without fallback)
        $trace = ImportResolutionTrace::getLastTrace();
        $this->assertNotNull($trace);
        $this->assertEquals('INFO', $trace['level']);
        $this->assertEquals('resolver-msg-1', $trace['google_message_id']);
        $this->assertFalse($trace['fallback_used']);
    }

    /**
     * Test 2: Resolver alias match when flag is ON.
     */
    public function test_resolver_alias_match_when_flag_on(): void
    {
        Config::set('finance.use_identity_resolver', true);
        IdentityResolutionStatistics::reset();

        // Register custom alias for the secondary account
        $this->resolver->registerIdentifier(
            accountId: $this->secondaryAccount->id,
            type: 'alias',
            value: 'SalaryAccount',
            provider: 'MANDIRI',
            priority: 50,
            source: 'manual'
        );

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'resolver-msg-2',
            'subject'           => 'Mandiri Salary',
            'from'              => 'mandiri@mandiri.co.id',
            'plain_body'        => "Tanggal : 06/08/2026\nJam : 12:00:00\nNominal : Rp. 50.000,00\nNo. Referensi : DBT99882211\nRekening Asal : SalaryAccount",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals($this->secondaryAccount->id, $tx->account_id);
        $this->assertEquals(1, IdentityResolutionStatistics::get('alias'));
    }

    /**
     * Test 3: Resolver failure falls back to legacy lookup successfully.
     */
    public function test_resolver_failure_falls_back_to_legacy(): void
    {
        Config::set('finance.use_identity_resolver', true);
        IdentityResolutionStatistics::reset();

        // Do not register any identifiers. Querying '1234' on BCA will fail in resolver,
        // but legacy lookup findAccount() will succeed on account_number '1234'.
        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'fallback-msg-1',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211\nDARI REKENING : 1234",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals($this->bankAccount->id, $tx->account_id);
        
        // Stat verification
        $this->assertEquals(1, IdentityResolutionStatistics::get('legacy_fallback'));
        
        // Trace level verification (should be WARNING because fallback was used)
        $trace = ImportResolutionTrace::getLastTrace();
        $this->assertNotNull($trace);
        $this->assertEquals('WARNING', $trace['level']);
        $this->assertTrue($trace['fallback_used']);
    }

    /**
     * Test 4: Feature flag OFF uses legacy lookup only.
     */
    public function test_feature_flag_off_uses_legacy_only(): void
    {
        Config::set('finance.use_identity_resolver', false);
        IdentityResolutionStatistics::reset();

        // Even if we register a conflicting identifier on secondaryAccount,
        // when flag is OFF, legacy lookup will run and match '1234' to bankAccount.
        $this->resolver->registerIdentifier(
            accountId: $this->secondaryAccount->id,
            type: 'account_number',
            value: '1234',
            provider: 'BCA',
            priority: 100,
            source: 'manual'
        );

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'flag-off-msg',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211\nDARI REKENING : 1234",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals($this->bankAccount->id, $tx->account_id); // legacy won
        
        // Stats should be 0 because resolver was bypassed
        $this->assertEquals(0, IdentityResolutionStatistics::get('exact_match'));
    }

    /**
     * Test 5: Feature flag ON prefers resolver over legacy.
     */
    public function test_feature_flag_on_prefers_resolver(): void
    {
        Config::set('finance.use_identity_resolver', true);
        IdentityResolutionStatistics::reset();

        // Secondary account intercepts '1234' lookup via identity resolution
        $this->resolver->registerIdentifier(
            accountId: $this->secondaryAccount->id,
            type: 'account_number',
            value: '1234',
            provider: 'BCA',
            priority: 100,
            source: 'manual'
        );

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'flag-on-msg',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211\nDARI REKENING : 1234",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $tx = Transaction::findOrFail($result->transactionId);
        $this->assertEquals($this->secondaryAccount->id, $tx->account_id); // resolver won
        $this->assertEquals(1, IdentityResolutionStatistics::get('exact_match'));
    }

    /**
     * Test 6: Ambiguous identifiers.
     */
    public function test_ambiguous_identifiers(): void
    {
        Config::set('finance.use_identity_resolver', true);
        IdentityResolutionStatistics::reset();

        // Register same alias on both accounts with same priority
        $this->resolver->registerIdentifier(
            accountId: $this->bankAccount->id,
            type: 'alias',
            value: 'SharedAlias',
            provider: 'BCA',
            priority: 10,
            source: 'manual'
        );

        $this->resolver->registerIdentifier(
            accountId: $this->secondaryAccount->id,
            type: 'alias',
            value: 'SharedAlias',
            provider: 'BCA',
            priority: 10,
            source: 'manual'
        );

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'ambig-msg',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211\nDARI REKENING : SharedAlias",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        // Since it's ambiguous, the resolver fails. Legacy fallback will try to find bank by fallback
        // which matches BCA (bankAccount). So import still succeeds via legacy fallback.
        $result = $this->importer->import($message);

        $this->assertEquals('SUCCESS', $result->status);
        $this->assertEquals(1, IdentityResolutionStatistics::get('ambiguous'));

        $trace = ImportResolutionTrace::getLastTrace();
        $this->assertNotNull($trace);
        $this->assertEquals('ERROR', $trace['level']); // Ambiguity is ERROR level
        $this->assertEquals('AMBIGUOUS', $trace['failure_code']);
    }

    public function test_no_account_resolved(): void
    {
        Config::set('finance.use_identity_resolver', true);
        IdentityResolutionStatistics::reset();

        $message = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_NEW,
            'google_message_id' => 'no-match-msg',
            'subject'           => 'BRI Debit Card',
            'from'              => 'bri@bri.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211\nDARI REKENING : 9999",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $parsedTx = new \App\DTO\ParsedTransaction(
            merchant: 'STARBUCKS',
            amount: 50000.00,
            transactionDate: now(),
            reference: 'DBT99882211',
            bank: 'UNKNOWN_BANK',
            flow: 'OUT',
            type: 'TRANSFER',
            sourceAccount: '9999',
            destinationAccount: null,
            rawAmount: 'Rp. 50.000,00',
            status: 'SUCCESS',
            confidence: \App\Enums\ConfidenceLevel::HIGH,
            notes: 'Test',
            template: 'TEST_TEMPLATE',
            counterparty: null,
            metadata: []
        );

        $parserResult = new \App\DTO\ParserResult(
            supported: true,
            warnings: [],
            errors: [],
            confidence: \App\Enums\ConfidenceLevel::HIGH,
            parsedMessage: new \App\DTO\ParsedMessage(
                provider: 'TEST',
                intent: 'TRANSACTION',
                confidence: \App\Enums\ConfidenceLevel::HIGH,
                rawFields: []
            ),
            parsedTransaction: $parsedTx
        );

        $result = $this->importer->import($message, $parserResult);

        $this->assertEquals('FAILED', $result->status);
        $this->assertEquals('Mapped financial account not found.', $result->message);
        $this->assertEquals(1, IdentityResolutionStatistics::get('no_match'));

        $trace = ImportResolutionTrace::getLastTrace();
        $this->assertNotNull($trace);
        $this->assertEquals('CRITICAL', $trace['level']); // No match resolved is CRITICAL level
        $this->assertEquals('NO_MATCH', $trace['failure_code']);
    }

    /**
     * Test 8: Legacy replay comparison.
     */
    public function test_legacy_replay_comparison(): void
    {
        // Setup two identical messages
        $message1 = InboxMessage::create([
            'user_id'           => $this->user->id,
            'connector'         => 'gmail',
            'status'            => InboxMessage::STATUS_IMPORTED,
            'google_message_id' => 'replay-msg-1',
            'subject'           => 'BCA Debit Card',
            'from'              => 'bca@bca.co.id',
            'plain_body'        => "TRANSAKSI KARTU DEBIT BERHASIL\nTANGGAL : 06/08/2026\nJAM : 12:00:00\nMERCHANT : STARBUCKS\nNOMINAL : Rp. 50.000,00\nNO. REFERENSI : DBT99882211\nDARI REKENING : 1234",
            'raw_payload'       => [],
            'labels'            => [],
            'internal_date'     => now()->timestamp * 1000,
            'imported_at'       => now()->subDays(2),
        ]);

        // Register exact account identifier so resolver succeeds exactly
        $this->resolver->registerIdentifier(
            accountId: $this->bankAccount->id,
            type: 'account_number',
            value: '1234',
            provider: 'BCA',
            priority: 100,
            source: 'manual'
        );

        // Run the replay imports compare command
        $this->artisan('finance:replay-imports', [
            '--resolver' => 'compare',
            '--since' => now()->subDays(5)->toDateString()
        ])->assertExitCode(0);
    }
}
