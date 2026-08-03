<?php

namespace App\Services\Parsers\Extractor;

use App\Contracts\DocumentExtractorInterface;

/**
 * Base abstract class for document extractors containing HTML/text cleaning utilities.
 */
abstract class AbstractExtractor implements DocumentExtractorInterface
{
    /**
     * Cleans an HTML block by stripping scripts/styles, tag elements, and normalizing whitespace.
     */
    public function cleanHtml(string $html): string
    {
        $text = preg_replace('/<(script|style)[^>]*?>.*?<\/\\1>/si', '', $html);
        $text = strip_tags($text);
        $text = $this->decodeEntities($text);
        return $this->normalizeWhitespace($text);
    }

    /**
     * Cleans a text block by normalizing newlines and whitespace.
     */
    public function cleanText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        return $this->normalizeWhitespace($text);
    }

    /**
     * Safe regular expression matching that returns the matched group or null.
     */
    public function extractRegex(string $text, string $pattern, int $group = 1): ?string
    {
        if (preg_match($pattern, $text, $matches)) {
            return $matches[$group] ?? null;
        }
        return null;
    }

    /**
     * Replaces tabs, newlines, non-breaking spaces, and duplicate spaces with a single space.
     */
    public function normalizeWhitespace(string $text): string
    {
        // Convert UTF-8 and ISO-8859-1 non-breaking spaces to standard space
        $text = str_replace(["\xc2\xa0", "\xa0"], ' ', $text);

        // Replace any sequence of whitespace characters with a single space
        $replaced = preg_replace('/\s+/', ' ', $text);
        if ($replaced !== null) {
            $text = $replaced;
        }

        return trim($text);
    }

    /**
     * Decodes HTML entities safely.
     */
    public function decodeEntities(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
