<?php

namespace App\Services\Finance\Identity\DTO;

use App\Models\Account;

/**
 * DTO representing the result of an account identity resolution.
 */
class ResolutionResult
{
    public function __construct(
        public readonly ?Account $account,
        public readonly string $originalIdentifier,
        public readonly string $normalizedIdentifier,
        public readonly ?string $identifierType,
        public readonly ?string $matchedBy,
        public readonly float $confidence, // 0.0 to 1.0
        public readonly ?string $provider,
        public readonly string $resolutionMethod,
        public readonly array $trace = [],
        public readonly ?string $failureCode = null
    ) {}

    /**
     * Get confidence rating as a percentage integer (e.g. 95).
     */
    public function confidencePercent(): int
    {
        return (int) round($this->confidence * 100);
    }

    /**
     * Return true if the resolution was successful (i.e. we found a valid account).
     */
    public function isSuccess(): bool
    {
        return $this->account !== null;
    }
}
