<?php

namespace App\Services\Finance;

/**
 * Handles raw merchant name normalization to clean up and unify merchant identifiers.
 */
class MerchantNormalizer
{
    /**
     * Normalize raw merchant name using simple aliases.
     */
    public function normalizeMerchant(string $merchant): string
    {
        $trimmed = trim($merchant);
        if ($trimmed === '') {
            return '';
        }

        $lower = strtolower($trimmed);

        // PT FINNET INDONESIA / BY.U -> by.U
        if (str_contains($lower, 'finnet') && str_contains($lower, 'by.u')) {
            return 'by.U';
        }

        // PT Tokopedia -> Tokopedia
        if (str_contains($lower, 'tokopedia')) {
            return 'Tokopedia';
        }

        // DOKU AGODA or NUSA SATU INTI ARTHA PT or Agoda Indonesia -> Agoda
        if (
            str_contains($lower, 'doku agoda') ||
            str_contains($lower, 'nusa satu inti artha') ||
            str_contains($lower, 'agoda indonesia')
        ) {
            return 'Agoda';
        }

        // IOH -> Indosat
        if (str_contains($lower, 'ioh') || str_contains($lower, 'indosat')) {
            return 'Indosat';
        }

        // ShopeePay
        if (str_contains($lower, 'shopeepay')) {
            return 'ShopeePay';
        }

        // GoPay
        if (str_contains($lower, 'gopay')) {
            return 'GoPay';
        }

        // OVO
        if (str_contains($lower, 'ovo')) {
            return 'OVO';
        }

        return $trimmed;
    }

    /**
     * Generate a unique lookup key for a merchant.
     */
    public function merchantKey(string $merchant): string
    {
        $key = strtolower($merchant);

        // Remove "qris " or "qris" (case-insensitive)
        $key = preg_replace('/\bqris\s*/i', '', $key);

        // Remove punctuation (keep letters, numbers, and spaces)
        $key = preg_replace('/[^\p{L}\p{N}\s]/u', '', $key);

        // Collapse multiple spaces and trim
        $key = preg_replace('/\s+/', ' ', $key);
        $key = trim($key);

        // Remove all spaces
        $key = str_replace(' ', '', $key);

        return $key;
    }
}
