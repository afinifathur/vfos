<?php

namespace App\DTO;

use App\Enums\ConfidenceLevel;

/**
 * Immutable DTO wrapping the complete outcome of a parser run.
 */
class ParserResult
{
    public function __construct(
        public readonly bool $supported,
        public readonly array $warnings = [],
        public readonly array $errors = [],
        public readonly ConfidenceLevel $confidence = ConfidenceLevel::LOW,
        public readonly ?ParsedMessage $parsedMessage = null,
        public readonly ?ParsedTransaction $parsedTransaction = null
    ) {}

    /**
     * Helper to instantiate a result when a parser does not support the message.
     */
    public static function unsupported(): self
    {
        return new self(
            supported: false,
            warnings: [],
            errors: [],
            confidence: ConfidenceLevel::LOW,
            parsedMessage: null,
            parsedTransaction: null
        );
    }
}
