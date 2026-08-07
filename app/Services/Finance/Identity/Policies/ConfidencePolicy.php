<?php

namespace App\Services\Finance\Identity\Policies;

/**
 * Defines the confidence scoring rules and thresholds for account identity resolution.
 */
class ConfidencePolicy
{
    // Confidence score mappings (represented as float 0.0 to 1.0)
    public const CONFIDENCE_MAP = [
        'account_number' => 1.0,     // 100% Exact account number
        'virtual_account' => 0.95,   // 95% Virtual account
        'phone' => 0.90,             // 90% Phone
        'email' => 0.90,             // 90% Email
        'alias' => 0.80,             // 80% Configured alias
        'display_name' => 0.60,      // 60% Display name
    ];

    // Suffix match confidence overrides
    public const CONFIDENCE_VIRTUAL_ACCOUNT_SUFFIX = 0.95; // 95% Suffix-wrapped account number
    public const CONFIDENCE_PARTIAL_SUFFIX = 0.80;         // 80% Suffix-partial account number

    // Thresholds
    public const THRESHOLD_STRICT = 0.80; // STRICT rejects low-confidence matches (< 80%)

    /**
     * Retrieve standard confidence score for a given identifier type.
     */
    public static function getConfidence(string $type): float
    {
        return self::CONFIDENCE_MAP[$type] ?? 0.0;
    }
}
