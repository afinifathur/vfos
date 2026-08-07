<?php

namespace App\Services\Finance\Diagnostics\DTO;

class FinancialIntegrityReport
{
    public function __construct(
        public readonly array $metadata,
        public readonly int $healthScore,
        public readonly string $certificationStatus, // READY FOR PRODUCTION, READY WITH WARNINGS, NOT READY
        public readonly array $severitySummary,
        public readonly array $categorySummary,
        public readonly IntegrityFindingCollection $findings,
        public readonly array $benchmarks = [],
        public readonly array $fingerprints = []
    ) {}

    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata,
            'health_score' => $this->healthScore,
            'certification_status' => $this->certificationStatus,
            'severity_summary' => $this->severitySummary,
            'category_summary' => $this->categorySummary,
            'findings' => $this->findings->all()->map(fn($f) => $f->toArray())->all(),
            'benchmarks' => $this->benchmarks,
            'fingerprints' => $this->fingerprints,
        ];
    }
}
