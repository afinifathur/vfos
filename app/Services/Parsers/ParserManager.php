<?php

namespace App\Services\Parsers;

use App\Contracts\DocumentExtractorInterface;
use App\Contracts\ParserInterface;
use App\DTO\ParserContext;
use App\DTO\ParserResult;
use App\Exceptions\UnsupportedMessageException;
use App\Models\InboxMessage;
use App\Services\Parsers\Extractor\BcaDocumentExtractor;
use App\Services\Parsers\Extractor\DefaultDocumentExtractor;
use App\Services\Parsers\Extractor\MandiriDocumentExtractor;

/**
 * Manager class directing InboxMessages through the Document Extraction layer
 * and routing to the appropriate parser.
 */
class ParserManager
{
    /** @var ParserInterface[] */
    private array $parsers = [];

    /** @var DocumentExtractorInterface[] */
    private array $extractors = [];

    /**
     * Accepts iterable collections of parsers and extractors.
     * Falls back to production defaults if none provided.
     *
     * @param iterable<ParserInterface> $parsers
     * @param iterable<DocumentExtractorInterface> $extractors
     */
    public function __construct(iterable $parsers = [], iterable $extractors = [])
    {
        $parsersArray = is_array($parsers) ? $parsers : iterator_to_array($parsers);
        $extractorsArray = is_array($extractors) ? $extractors : iterator_to_array($extractors);

        // Register default production components if not provided
        if (empty($parsersArray)) {
            $parsersArray = [
                app(BCAParser::class),
                app(MandiriParser::class),
            ];
        }

        if (empty($extractorsArray)) {
            $extractorsArray = [
                app(BcaDocumentExtractor::class),
                app(MandiriDocumentExtractor::class),
            ];
        }

        foreach ($parsersArray as $parser) {
            $this->registerParser($parser);
        }

        foreach ($extractorsArray as $extractor) {
            $this->registerExtractor($extractor);
        }
    }


    /**
     * Manually register a parser instance.
     */
    public function registerParser(ParserInterface $parser): void
    {
        $this->parsers[] = $parser;
    }

    /**
     * Manually register an extractor instance.
     */
    public function registerExtractor(DocumentExtractorInterface $extractor): void
    {
        $this->extractors[] = $extractor;
    }

    /**
     * Get all currently registered parsers.
     *
     * @return ParserInterface[]
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * Get all currently registered extractors.
     *
     * @return DocumentExtractorInterface[]
     */
    public function getExtractors(): array
    {
        return $this->extractors;
    }

    /**
     * Route a message to the supporting parser and return the result.
     *
     * @throws UnsupportedMessageException If no parser supports the message
     */
    public function parse(InboxMessage $message, ParserContext $context): ParserResult
    {
        // 1. Resolve Document Extractor
        $extractor = null;
        foreach ($this->extractors as $ext) {
            if ($ext->supports($message)) {
                $extractor = $ext;
                break;
            }
        }

        if (!$extractor) {
            $extractor = new DefaultDocumentExtractor();
        }

        // 2. Perform Document Extraction (Revision 1, 2 & 6)
        $document = $extractor->extract($message);

        // 3. Find and run supporting Parser
        foreach ($this->parsers as $parser) {
            if ($parser->supports($document, $context)) {
                $result = $parser->parse($document, $context);
                if ($result->supported) {
                    return $result;
                }
            }
        }

        throw new UnsupportedMessageException(sprintf(
            "No supporting parser found for message ID %s (Connector: %s).",
            $message->google_message_id,
            $context->connector
        ));
    }
}
