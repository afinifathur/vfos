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
 * Regression test suite verifying MandiriParser logic.
 *
 * Covers confirmed production templates: TRANSFER, BIFAST, QRIS, TOPUP.
 * Expectations represent canonical business behavior.
 */
class MandiriParserTest extends TestCase
{
    use RefreshDatabase;

    private ParserManager $parserManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parserManager = app(ParserManager::class);
    }

    /**
     * Run regression assertions on offline Mandiri fixtures.
     */
    public function test_mandiri_parser_offline_fixtures(): void
    {
        $fixturesPath = base_path('tests/Fixtures/Mandiri/offline_fixtures.json');
        $this->assertFileExists($fixturesPath, 'Mandiri offline fixtures file is missing.');

        $fixtures = json_decode(file_get_contents($fixturesPath), true);
        $this->assertNotEmpty($fixtures, 'Mandiri offline fixtures are empty.');

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

            $this->assertTrue($result->supported, "Fixture [{$msg->google_message_id}] should be supported by Mandiri parser.");
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
     * Verify the parser produces a MANDIRI bank value.
     */
    public function test_mandiri_parser_sets_correct_bank(): void
    {
        $context = new ParserContext(connector: 'gmail');

        $msg = new InboxMessage([
            'google_message_id' => 'mandiri-bank-check',
            'subject'           => 'Mandiri Transfer Keluar',
            'from'              => 'notifikasi@bankmandiri.co.id',
            'plain_body'        => "Transaksi Transfer\nTanggal : 10/07/2026\nWaktu : 13:00:00\nRekening Asal : 1234567890\nRekening Tujuan : 9876543210\nNama Penerima : TEST USER\nNominal : Rp. 100.000,00\nKeterangan : Test\nNo. Referensi : TEST001",
            'html_body'         => null,
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->parserManager->parse($msg, $context);

        $this->assertTrue($result->supported);
        $this->assertNotNull($result->parsedTransaction);
        $this->assertEquals('MANDIRI', $result->parsedTransaction->bank);
        $this->assertEquals('MANDIRI', $result->parsedMessage->provider);
    }

    /**
     * Verify TransactionDescriptionBuilder produces concise descriptions for Mandiri transactions.
     */
    public function test_mandiri_description_builder_produces_concise_labels(): void
    {
        $context = new ParserContext(connector: 'gmail');
        $builder = app(\App\Services\Finance\TransactionDescriptionBuilder::class);

        // Transfer OUT with keterangan
        $msg = new InboxMessage([
            'google_message_id' => 'mandiri-desc-kost',
            'subject'           => 'Mandiri Transfer Keluar',
            'from'              => 'notifikasi@bankmandiri.co.id',
            'plain_body'        => "Transaksi Transfer\nTanggal : 10/07/2026\nWaktu : 13:00:00\nNama Penerima : INAYAH TOYIBAH\nNominal : Rp. 750.000,00\nKeterangan : Kost Agustus\nNo. Referensi : MDR001",
            'html_body'         => null,
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result = $this->parserManager->parse($msg, $context);
        $this->assertNotNull($result->parsedTransaction);

        $description = $builder->build($result->parsedTransaction);
        // Remarks "Kost Agustus" should take priority over counterparty
        $this->assertEquals('Kost Agustus', $description);

        // QRIS
        $msg2 = new InboxMessage([
            'google_message_id' => 'mandiri-desc-qris',
            'subject'           => 'Pembayaran QRIS Mandiri',
            'from'              => 'notifikasi@bankmandiri.co.id',
            'plain_body'        => "Transaksi QRIS Berhasil\nTanggal : 15/07/2026\nWaktu : 19:00:00\nMerchant : WARUNG MANADO\nNominal : Rp. 35.000,00\nNo. Referensi : QMDRI001",
            'html_body'         => null,
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result2 = $this->parserManager->parse($msg2, $context);
        $this->assertNotNull($result2->parsedTransaction);

        $description2 = $builder->build($result2->parsedTransaction);
        $this->assertEquals('QRIS Warung Manado', $description2);

        // Top Up
        $msg3 = new InboxMessage([
            'google_message_id' => 'mandiri-desc-topup',
            'subject'           => 'Top Up ShopeePay Berhasil',
            'from'              => 'notifikasi@bankmandiri.co.id',
            'plain_body'        => "Top Up Berhasil\nTanggal : 20/07/2026\nWaktu : 10:15:00\nTarget Dompet : ShopeePay\nNominal : Rp. 100.000,00\nNo. Referensi : TPSP001",
            'html_body'         => null,
            'internal_date'     => now()->timestamp * 1000,
        ]);

        $result3 = $this->parserManager->parse($msg3, $context);
        $this->assertNotNull($result3->parsedTransaction);

        $description3 = $builder->build($result3->parsedTransaction);
        $this->assertEquals('Top Up ShopeePay', $description3);

        // Virtual Account-style structured description
        $tx4 = new \App\DTO\ParsedTransaction(
            merchant: 'by.U',
            amount: 50000,
            transactionDate: Carbon::parse('2026-07-21 12:00:00'),
            reference: 'VA001',
            bank: 'MANDIRI',
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
        $this->assertEquals('by.U SuperKaget3GB', $builder->build($tx4));
    }
}
