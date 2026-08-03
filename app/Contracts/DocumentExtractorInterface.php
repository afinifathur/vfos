<?php

namespace App\Contracts;

use App\DTO\NormalizedDocument;
use App\Models\InboxMessage;

/**
 * Interface contract to be implemented by all document extractors.
 */
interface DocumentExtractorInterface
{
    /**
     * Determine if this extractor supports the given message.
     */
    public function supports(InboxMessage $message): bool;

    /**
     * Extract a normalized document (HTML cleanup, whitespace cleanup, regex extraction) from a message.
     */
    public function extract(InboxMessage $message): NormalizedDocument;
}
