<?php

namespace Tests\Feature\Parser;

use App\DTO\ParserContext;
use App\Enums\ConfidenceLevel;
use App\Models\InboxMessage;
use App\Services\Parsers\ParserManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test suite verifying the BCAParser logic.
 *
 * Expectations represent canonical business behavior — not legacy wording.
 * When parser logic changes intentionally, update fixtures first, then tests.
 */
class BCAParserTest extends TestCase
{
    use RefreshDatabase;

    private ParserManager $parserManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parserManager = app(ParserManager::class);
    }

    /**
     * Run regression assertions on offline fixtures to guarantee logic correctness.
     */
    public function test_bca_parser_offline_fixtures(): void
    {
        $fixturesPath = base_path('tests/Fixtures/BCA/offline_fixtures.json');
        $this->assertFileExists($fixturesPath, 'BCA offline fixtures file is missing.');

        $fixtures = json_decode(file_get_contents($fixturesPath), true);
        $this->assertNotEmpty($fixtures, 'BCA offline fixtures are empty.');

        $context = new ParserContext(connector: 'gmail');

        foreach ($fixtures as $fixture) {
            $msg = new InboxMessage([
                'google_message_id' => $fixture['google_message_id'],
                'subject'           => $fixture['subject'],
                'from'              => $fixture['from'],
                'plain_body'        => $fixture['plain_body'],
                'html_body'         => $fixture['html_body'] ?? null,
                'internal_date'     => now()->timestamp * 1000,
            ]);

            $result = $this->parserManager->parse($msg, $context);

            $this->assertTrue($result->supported, "Fixture [{$msg->google_message_id}] should be supported.");
            $this->assertNotNull($result->parsedMessage, "ParsedMessage must not be null.");

            $exp = $fixture['expected'];
            $tx  = $result->parsedTransaction;

            if ($exp['confidence'] === 'LOW' && $tx === null) {
                $this->assertEquals(ConfidenceLevel::LOW, $result->confidence);
                continue;
            }

            $this->assertNotNull($tx, "ParsedTransaction must not be null for [{$msg->google_message_id}].");

            $this->assertEquals($exp['merchant'], $tx->merchant, "Merchant mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['amount'], $tx->amount, "Amount mismatch on [{$msg->google_message_id}].");

            if ($exp['date']) {
                $expectedDate = Carbon::parse($exp['date']);
                $this->assertTrue(
                    $expectedDate->equalTo($tx->transactionDate),
                    "Date mismatch on [{$msg->google_message_id}]. Expected: {$expectedDate->toDateTimeString()}, Got: {$tx->transactionDate->toDateTimeString()}"
                );
            } else {
                $this->assertNull($tx->transactionDate);
            }

            $this->assertEquals($exp['reference'], $tx->reference, "Reference mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['flow'], $tx->flow, "Flow mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['type'], $tx->type, "Type mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['sourceAccount'], $tx->sourceAccount, "Source Account mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['destinationAccount'], $tx->destinationAccount, "Destination Account mismatch on [{$msg->google_message_id}].");

            // Template & counterparty are new canonical fields
            if (array_key_exists('template', $exp)) {
                $this->assertEquals($exp['template'], $tx->template, "Template mismatch on [{$msg->google_message_id}].");
            }
            if (array_key_exists('counterparty', $exp)) {
                $this->assertEquals($exp['counterparty'], $tx->counterparty, "Counterparty mismatch on [{$msg->google_message_id}].");
            }

            $expectedConfidence = match ($exp['confidence']) {
                'HIGH'   => ConfidenceLevel::HIGH,
                'MEDIUM' => ConfidenceLevel::MEDIUM,
                'LOW'    => ConfidenceLevel::LOW,
            };
            $this->assertEquals($expectedConfidence, $tx->confidence, "Confidence mismatch on [{$msg->google_message_id}].");
        }
    }

    /**
     * Run regression assertions on all available July 2026 BCA emails in the database.
     */
    public function test_bca_parser_database_july_2026_regression(): void
    {
        $julyStartMs = Carbon::parse('2026-07-01 00:00:00', 'Asia/Jakarta')->timestamp * 1000;
        $julyEndMs   = Carbon::parse('2026-07-31 23:59:59', 'Asia/Jakarta')->timestamp * 1000;

        $dbMessages = InboxMessage::where(function ($q) {
                $q->where('from', 'like', '%bca.co.id%')
                  ->orWhere('from', 'like', '%cba.co.id%')
                  ->orWhere('subject', 'like', '%Transfer%')
                  ->orWhere('subject', 'like', '%QRIS%')
                  ->orWhere('subject', 'like', '%Debit%')
                  ->orWhere('subject', 'like', '%Credit%');
            })
            ->whereBetween('internal_date', [$julyStartMs, $julyEndMs])
            ->get();

        if ($dbMessages->isEmpty()) {
            $this->markTestSkipped('No July 2026 BCA messages found in the database. Skipping database regression.');
        }

        $expectationsPath = base_path('tests/Fixtures/BCA/regression_expectations.json');
        $expectations = [];
        if (file_exists($expectationsPath)) {
            $expectations = json_decode(file_get_contents($expectationsPath), true) ?: [];
        }

        $context = new ParserContext(connector: 'gmail');

        foreach ($dbMessages as $msg) {
            $result = $this->parserManager->parse($msg, $context);

            $this->assertTrue($result->supported, "Database message [{$msg->google_message_id}] should be supported by BCA parser.");

            if (isset($expectations[$msg->google_message_id])) {
                $exp = $expectations[$msg->google_message_id];
                $tx  = $result->parsedTransaction;

                $this->assertNotNull($tx, "ParsedTransaction should not be null for mapped message [{$msg->google_message_id}].");
                $this->assertEquals($exp['merchant'], $tx->merchant);
                $this->assertEquals($exp['amount'], $tx->amount);
                $this->assertTrue(Carbon::parse($exp['date'])->equalTo($tx->transactionDate));
                $this->assertEquals($exp['reference'], $tx->reference);
                $this->assertEquals($exp['flow'], $tx->flow);
                $this->assertEquals($exp['type'], $tx->type);
                $this->assertEquals($exp['sourceAccount'], $tx->sourceAccount);
                $this->assertEquals($exp['destinationAccount'], $tx->destinationAccount);

                $expectedConfidence = match ($exp['confidence']) {
                    'HIGH'   => ConfidenceLevel::HIGH,
                    'MEDIUM' => ConfidenceLevel::MEDIUM,
                    'LOW'    => ConfidenceLevel::LOW,
                };
                $this->assertEquals($expectedConfidence, $tx->confidence);
            } else {
                $tx = $result->parsedTransaction;
                if ($tx !== null) {
                    $this->assertGreaterThan(0, $tx->amount, "Amount should be positive for [{$msg->google_message_id}].");
                    $this->assertNotNull($tx->transactionDate, "Date should be parsed for [{$msg->google_message_id}].");
                    $this->assertNotEmpty($tx->flow);
                    $this->assertNotEmpty($tx->type);
                }
            }
        }
    }

    /**
     * Verify TransactionDescriptionBuilder produces concise labels for BCA templates.
     */
    public function test_bca_description_builder_produces_concise_labels(): void
    {
        $builder = app(\App\Services\Finance\TransactionDescriptionBuilder::class);

        $qris = new \App\DTO\ParsedTransaction(
            merchant: 'WARUNG MANADO',
            amount: 45500,
            transactionDate: Carbon::parse('2026-07-20 19:45:30'),
            reference: 'QRS12345678',
            bank: 'BCA',
            flow: 'OUT',
            type: 'QRIS',
            sourceAccount: null,
            destinationAccount: null,
            rawAmount: 'Rp. 45.500,00',
            status: 'SUCCESS',
            confidence: ConfidenceLevel::HIGH,
            notes: null,
            template: 'BCA_QRIS',
            counterparty: null,
        );
        $this->assertEquals('QRIS Warung Manado', $builder->build($qris));

        $transfer = new \App\DTO\ParsedTransaction(
            merchant: 'INAYAH TOYIBAH',
            amount: 750000,
            transactionDate: Carbon::parse('2026-07-10 13:00:00'),
            reference: 'MDR77301928',
            bank: 'BCA',
            flow: 'OUT',
            type: 'TRANSFER',
            sourceAccount: '1234567890',
            destinationAccount: '9876543210',
            rawAmount: 'Rp. 750.000,00',
            status: 'SUCCESS',
            confidence: ConfidenceLevel::HIGH,
            notes: null,
            template: 'BCA_TRANSFER',
            counterparty: 'INAYAH TOYIBAH',
        );
        $this->assertEquals('Transfer to Inayah Toyibah', $builder->build($transfer));

        $cashDeposit = new \App\DTO\ParsedTransaction(
            merchant: null,
            amount: 500000,
            transactionDate: Carbon::parse('2026-08-01 10:00:00'),
            reference: 'STN00112233',
            bank: 'BCA',
            flow: 'IN',
            type: 'CASH_DEPOSIT',
            sourceAccount: null,
            destinationAccount: null,
            rawAmount: 'Rp. 500.000,00',
            status: 'SUCCESS',
            confidence: ConfidenceLevel::MEDIUM,
            notes: null,
            template: 'BCA_CASH_DEPOSIT',
            counterparty: null,
        );
        $this->assertEquals('Cash Deposit', $builder->build($cashDeposit));

        $cashWithdraw = new \App\DTO\ParsedTransaction(
            merchant: null,
            amount: 300000,
            transactionDate: Carbon::parse('2026-08-01 11:30:00'),
            reference: 'TTN00445566',
            bank: 'BCA',
            flow: 'OUT',
            type: 'CASH_WITHDRAW',
            sourceAccount: null,
            destinationAccount: null,
            rawAmount: 'Rp. 300.000,00',
            status: 'SUCCESS',
            confidence: ConfidenceLevel::MEDIUM,
            notes: null,
            template: 'BCA_CASH_WITHDRAW',
            counterparty: null,
        );
        $this->assertEquals('Cash Withdrawal', $builder->build($cashWithdraw));

        $va = new \App\DTO\ParsedTransaction(
            merchant: 'by.U',
            amount: 50000,
            transactionDate: Carbon::parse('2026-07-21 12:00:00'),
            reference: 'VA001',
            bank: 'BCA',
            flow: 'OUT',
            type: 'TRANSFER',
            sourceAccount: null,
            destinationAccount: null,
            rawAmount: 'Rp. 50.000,00',
            status: 'SUCCESS',
            confidence: ConfidenceLevel::HIGH,
            notes: null,
            template: 'BCA_VIRTUAL_ACCOUNT',
            counterparty: 'SuperKaget3GB',
        );
        $this->assertEquals('by.U SuperKaget3GB', $builder->build($va));
    }
}
