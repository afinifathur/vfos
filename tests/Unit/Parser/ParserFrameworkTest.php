<?php

namespace Tests\Unit\Parser;

use App\Contracts\ParserInterface;
use App\DTO\NormalizedDocument;
use App\DTO\ParsedMessage;
use App\DTO\ParsedTransaction;
use App\DTO\ParserContext;
use App\DTO\ParserResult;
use App\Enums\ConfidenceLevel;
use App\Exceptions\UnsupportedMessageException;
use App\Models\InboxMessage;
use App\Services\Parsers\AbstractParser;
use App\Services\Parsers\ParserManager;
use Carbon\Carbon;
use Error;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests verifying the Generic Parser Framework architecture, DTO immutability,
 * routing, auto-discovery, and parser normalization helpers.
 */
class ParserFrameworkTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ─── DTO Immutability Tests ──────────────────────────────────────────────

    public function test_parsed_transaction_dto_is_immutable(): void
    {
        $dto = new ParsedTransaction(
            merchant: 'BCA',
            amount: 50000, // Rp 50.000 stored in integer rupiah
            transactionDate: Carbon::parse('2026-08-02 13:28:00'),
            reference: 'REF123',
            bank: 'BCA',
            flow: 'OUT',
            type: 'TRANSFER',
            sourceAccount: '12345',
            destinationAccount: '67890',
            rawAmount: 'Rp 50.000',
            status: 'SUCCESS',
            confidence: ConfidenceLevel::HIGH
        );

        $this->assertEquals(50000, $dto->amount);
        $this->assertEquals('BCA', $dto->merchant);

        // Assert that modifying readonly properties throws a PHP Error
        $this->expectException(Error::class);
        /** @noinspection PhpUnsupportedMemberSignatureInspection */
        $dto->amount = 60000;
    }

    public function test_parsed_message_dto_is_immutable(): void
    {
        $dto = new ParsedMessage(
            provider: 'BCA',
            intent: 'TRANSACTION',
            confidence: ConfidenceLevel::MEDIUM,
            rawFields: ['amt' => 'Rp 50.000'],
            warnings: ['Spam flag'],
            errors: []
        );

        $this->assertEquals('TRANSACTION', $dto->intent);
        $this->assertEquals('BCA', $dto->provider);

        $this->expectException(Error::class);
        /** @noinspection PhpUnsupportedMemberSignatureInspection */
        $dto->intent = 'STATEMENT';
    }

    // ─── Routing & Auto-Discovery Tests ──────────────────────────────────────

    public function test_unsupported_message_throws_exception(): void
    {
        $manager = new ParserManager([], []);

        $message = new InboxMessage(['google_message_id' => '12345']);
        $context = new ParserContext(connector: 'gmail');

        $this->expectException(UnsupportedMessageException::class);
        $manager->parse($message, $context);
    }

    public function test_manager_auto_discovers_parsers_via_constructor(): void
    {
        $parser1 = Mockery::mock(ParserInterface::class);
        $parser2 = Mockery::mock(ParserInterface::class);

        $manager = new ParserManager([$parser1, $parser2], []);

        $this->assertCount(2, $manager->getParsers());
        $this->assertSame($parser1, $manager->getParsers()[0]);
        $this->assertSame($parser2, $manager->getParsers()[1]);
    }

    public function test_manager_routes_to_correct_supporting_parser(): void
    {
        $message = new InboxMessage(['google_message_id' => 'abc']);
        $context = new ParserContext(connector: 'gmail');

        // Parser A does not support this message
        $parserA = Mockery::mock(ParserInterface::class);
        $parserA->shouldReceive('supports')->once()->with(Mockery::any(), $context)->andReturn(false);
        $parserA->shouldNotReceive('parse');

        // Parser B supports this message
        $expectedResult = new ParserResult(
            supported: true,
            warnings: [],
            errors: [],
            confidence: ConfidenceLevel::HIGH,
            parsedMessage: new ParsedMessage('BCA', 'TRANSACTION', ConfidenceLevel::HIGH),
            parsedTransaction: new ParsedTransaction(
                merchant: 'Indomaret',
                amount: 10000,
                transactionDate: Carbon::now(),
                reference: 'REF-B',
                bank: 'BCA',
                flow: 'OUT',
                type: 'PAYMENT',
                sourceAccount: '123',
                destinationAccount: '456',
                rawAmount: '100',
                status: 'SUCCESS',
                confidence: ConfidenceLevel::HIGH
            )
        );

        $parserB = Mockery::mock(ParserInterface::class);
        $parserB->shouldReceive('supports')->once()->with(Mockery::any(), $context)->andReturn(true);
        $parserB->shouldReceive('parse')->once()->with(Mockery::any(), $context)->andReturn($expectedResult);

        $manager = new ParserManager([$parserA, $parserB], []);
        $result = $manager->parse($message, $context);

        $this->assertTrue($result->supported);
        $this->assertSame($expectedResult, $result);
    }

    // ─── AbstractParser Helper Tests ─────────────────────────────────────────

    public function test_abstract_parser_helpers(): void
    {
        // Instantiate a stub representing the AbstractParser
        $parser = new class extends AbstractParser {
            public function supports(NormalizedDocument $document, ParserContext $context): bool { return true; }
            public function parse(NormalizedDocument $document, ParserContext $context): ParserResult { return ParserResult::unsupported(); }
        };

        // 1. cleanHtml
        $dirtyHtml = "<script>alert('hack');</script><style>p { color: red; }</style><p>Hello &amp; <b>World</b></p>";
        $this->assertEquals("Hello & World", $parser->cleanHtml($dirtyHtml));

        // 2. cleanText
        $dirtyText = "Line 1\r\nLine 2\r   Multiple    Spaces";
        $this->assertEquals("Line 1 Line 2 Multiple Spaces", $parser->cleanText($dirtyText));

        // 3. normalizeWhitespace
        $wsText = "Hello \xA0 \t \n  World";
        $this->assertEquals("Hello World", $parser->normalizeWhitespace($wsText));

        // 4. extractRegex
        $subjectText = "Transfer dari BNI sebesar Rp 250.000 berhasil dilakukan.";
        $this->assertEquals("250.000", $parser->extractRegex($subjectText, '/Rp ([\d.]+)/'));
        $this->assertNull($parser->extractRegex($subjectText, '/Rp ([a-zA-Z]+)/')); // mismatched

        // 5. cleanCurrency (Indonesian vs English formatting)
        $this->assertEquals(50000.0, $parser->cleanCurrency('Rp 50.000,00'));
        $this->assertEquals(50000.0, $parser->cleanCurrency('$50,000.00'));
        $this->assertEquals(50000.0, $parser->cleanCurrency('Rp. 50.000'));
        $this->assertEquals(1250000.5, $parser->cleanCurrency('1.250.000,50'));

        // 6. parseDate
        $date = $parser->parseDate('02/08/2026 13:28', 'd/m/Y H:i');
        $this->assertNotNull($date);
        $this->assertEquals(2026, $date->year);
        $this->assertEquals(8, $date->month);
        $this->assertEquals(2, $date->day);
        $this->assertEquals(13, $date->hour);
        $this->assertEquals(28, $date->minute);
        $this->assertNull($parser->parseDate('invalid-date', 'd/m/Y H:i'));

        // 7. extractAmount
        $textWithAmount = "Pembayaran Tokopedia Rp 1.500.000 sukses";
        $this->assertEquals(1500000.0, $parser->extractAmount($textWithAmount, '/Rp ([\d.]+)/'));

        // 8. decodeEntities
        $this->assertEquals("Quote: ' & >", $parser->decodeEntities("Quote: &#039; &amp; &gt;"));
    }
}
