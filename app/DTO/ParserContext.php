<?php

namespace App\DTO;

/**
 * Immutable context for parsing messages, avoiding direct dependencies on InboxMessage structure.
 */
class ParserContext
{
    public function __construct(
        public readonly string $connector,
        public readonly string $locale = 'id_ID',
        public readonly string $timezone = 'Asia/Jakarta',
        public readonly array $metadata = []
    ) {}
}
