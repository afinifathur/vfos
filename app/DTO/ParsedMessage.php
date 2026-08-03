<?php

namespace App\DTO;

use App\Enums\ConfidenceLevel;

/**
 * Immutable DTO representing a raw parsed message categorization and parsed attributes.
 */
class ParsedMessage
{
    /**
     * @param string|null $provider
     * @param string $intent
     * @param ConfidenceLevel $confidence
     * @param array $rawFields
     * @param array $warnings
     * @param array $errors
     */
    public function __construct(
        public readonly ?string $provider,
        public readonly string $intent,
        public readonly ConfidenceLevel $confidence,
        public readonly array $rawFields = [],
        public readonly array $warnings = [],
        public readonly array $errors = []
    ) {}
}
