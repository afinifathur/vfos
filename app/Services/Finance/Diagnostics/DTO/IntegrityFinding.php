<?php

namespace App\Services\Finance\Diagnostics\DTO;

class IntegrityFinding
{
    public function __construct(
        public readonly string $category,
        public readonly string $severity, // INFO, WARNING, ERROR, CRITICAL
        public readonly string $issue,
        public readonly ?string $affectedObject,
        public readonly mixed $currentValue,
        public readonly mixed $expectedValue,
        public readonly mixed $difference = null,
        public readonly ?string $rootCause = null,
        public readonly ?string $recommendation = null
    ) {}

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'severity' => $this->severity,
            'issue' => $this->issue,
            'affected_object' => $this->affectedObject,
            'current_value' => $this->currentValue,
            'expected_value' => $this->expectedValue,
            'difference' => $this->difference,
            'root_cause' => $this->rootCause,
            'recommendation' => $this->recommendation,
        ];
    }
}
