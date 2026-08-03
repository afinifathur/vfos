<?php

namespace App\Services\Parsers;

use App\Contracts\ParserInterface;
use Carbon\Carbon;

/**
 * Base abstract class for parsers containing shared helper utilities.
 */
abstract class AbstractParser implements ParserInterface
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
     * Normalizes currency strings (handles both Rp 50.000,00 and $50,000.00) and returns a float.
     */
    public function cleanCurrency(string $amountStr): float
    {
        $clean = preg_replace('/[^\d.,]/', '', $amountStr);

        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                // Indonesian format: 50.000,00
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // English format: 50,000.00
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($lastComma !== false) {
            // Check if thousands separator or decimal separator
            $afterCommaLength = strlen($clean) - $lastComma - 1;
            if ($afterCommaLength === 2) {
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($lastDot !== false) {
            // Check if thousands separator or decimal separator
            $afterDotLength = strlen($clean) - $lastDot - 1;
            if ($afterDotLength !== 2) {
                $clean = str_replace('.', '', $clean);
            }
        }

        return (float) $clean;
    }

    /**
     * Parses a date string safely given a format and timezone.
     */
    public function parseDate(string $dateStr, string $format, ?string $timezone = 'Asia/Jakarta'): ?Carbon
    {
        try {
            return Carbon::createFromFormat($format, trim($dateStr), $timezone);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts an amount matching a pattern from text and runs it through cleanCurrency.
     */
    public function extractAmount(string $text, string $pattern): float
    {
        $amountStr = $this->extractRegex($text, $pattern);
        return $amountStr ? $this->cleanCurrency($amountStr) : 0.0;
    }

    /**
     * Decodes HTML entities safely.
     */
    public function decodeEntities(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
