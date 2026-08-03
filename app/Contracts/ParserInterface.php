<?php

namespace App\Contracts;

use App\DTO\NormalizedDocument;
use App\DTO\ParserContext;
use App\DTO\ParserResult;

/**
 * Interface contract to be implemented by all concrete financial parsers.
 */
interface ParserInterface
{
    /**
     * Determine if the parser supports the given message.
     */
    public function supports(NormalizedDocument $document, ParserContext $context): bool;

    /**
     * Parse the message and return a ParserResult containing message details and optional transaction.
     */
    public function parse(NormalizedDocument $document, ParserContext $context): ParserResult;
}

