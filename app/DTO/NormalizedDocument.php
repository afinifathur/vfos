<?php

namespace App\DTO;

/**
 * Represents a document normalized by the Extraction Layer.
 */
class NormalizedDocument
{
    public function __construct(
        public readonly string $subject,
        public readonly string $from,
        public readonly string $to,
        public readonly int $internalDate,
        public readonly string $body,
        public readonly FieldBag $fields
    ) {}
}
