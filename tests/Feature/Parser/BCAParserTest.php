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
 * Ensures parity with n8n JavaScript reference implementation.
 */
class BCAParserTest extends TestCase
{
    use RefreshDatabase;

    private ParserManager $parserManager;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolve ParserManager from Laravel Container (registers production defaults)
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
            $tx = $result->parsedTransaction;

            if ($exp['confidence'] === 'LOW' && $tx === null) {
                // Low confidence may not yield a transaction DTO, which is correct
                $this->assertEquals(ConfidenceLevel::LOW, $result->confidence);
                continue;
            }

            $this->assertNotNull($tx, "ParsedTransaction must not be null for [{$msg->google_message_id}].");

            $this->assertEquals($exp['merchant'], $tx->merchant, "Merchant mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['amount'], $tx->amount, "Amount mismatch on [{$msg->google_message_id}].");

            if ($exp['date']) {
                $expectedDate = Carbon::parse($exp['date']);
                $this->assertTrue($expectedDate->equalTo($tx->transactionDate), "Date mismatch on [{$msg->google_message_id}]. Expected: {$expectedDate->toDateTimeString()}, Got: {$tx->transactionDate->toDateTimeString()}");
            } else {
                $this->assertNull($tx->transactionDate);
            }

            $this->assertEquals($exp['reference'], $tx->reference, "Reference mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['flow'], $tx->flow, "Flow mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['type'], $tx->type, "Type mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['sourceAccount'], $tx->sourceAccount, "Source Account mismatch on [{$msg->google_message_id}].");
            $this->assertEquals($exp['destinationAccount'], $tx->destinationAccount, "Destination Account mismatch on [{$msg->google_message_id}].");

            $expectedConfidence = match ($exp['confidence']) {
                'HIGH' => ConfidenceLevel::HIGH,
                'MEDIUM' => ConfidenceLevel::MEDIUM,
                'LOW' => ConfidenceLevel::LOW,
            };
            $this->assertEquals($expectedConfidence, $tx->confidence, "Confidence mismatch on [{$msg->google_message_id}].");
        }
    }

    /**
     * Run regression assertions on all available July 2026 BCA emails in the database.
     * Evaluates dynamically based on the regression expectations map.
     */
    public function test_bca_parser_database_july_2026_regression(): void
    {
        // 1. Check if any BCA messages exist in the database for July 2026
        // July 2026 timestamps in milliseconds
        $julyStartMs = Carbon::parse('2026-07-01 00:00:00', 'Asia/Jakarta')->timestamp * 1000;
        $julyEndMs = Carbon::parse('2026-07-31 23:59:59', 'Asia/Jakarta')->timestamp * 1000;

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

        // 2. Load expectations map
        $expectationsPath = base_path('tests/Fixtures/BCA/regression_expectations.json');
        $expectations = [];
        if (file_exists($expectationsPath)) {
            $expectations = json_decode(file_get_contents($expectationsPath), true) ?: [];
        }

        $context = new ParserContext(connector: 'gmail');

        foreach ($dbMessages as $msg) {
            $result = $this->parserManager->parse($msg, $context);

            $this->assertTrue($result->supported, "Database message [{$msg->google_message_id}] should be supported by BCA parser.");

            // If expectations are mapped for this message, assert them strictly
            if (isset($expectations[$msg->google_message_id])) {
                $exp = $expectations[$msg->google_message_id];
                $tx = $result->parsedTransaction;

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
                    'HIGH' => ConfidenceLevel::HIGH,
                    'MEDIUM' => ConfidenceLevel::MEDIUM,
                    'LOW' => ConfidenceLevel::LOW,
                };
                $this->assertEquals($expectedConfidence, $tx->confidence);
            } else {
                // Otherwise run a basic sanity check
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
}
