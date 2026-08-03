<?php

namespace App\Services\Parsers\Extractor;

use App\DTO\FieldBag;
use App\DTO\NormalizedDocument;
use App\Models\InboxMessage;

/**
 * Fallback document extractor that normalizes HTML/Text without custom field extraction.
 */
class DefaultDocumentExtractor extends AbstractExtractor
{
    public function supports(InboxMessage $message): bool
    {
        return true;
    }

    public function extract(InboxMessage $message): NormalizedDocument
    {
        $body = $message->plain_body;
        if (empty($body) && !empty($message->html_body)) {
            $body = $this->cleanHtml($message->html_body);
        } else {
            $body = $this->cleanText($body ?? '');
        }

        return new NormalizedDocument(
            subject: $message->subject ?? '',
            from: $message->from ?? '',
            to: $message->to ?? '',
            internalDate: $message->internal_date ?? 0,
            body: $body,
            fields: new FieldBag()
        );
    }
}
