<?php

namespace App\Services\Finance\Diagnostics;

use App\Services\Finance\Diagnostics\DTO\IntegrityFindingCollection;

class FinancialHealthPolicy
{
    private array $deductions = [
        'CRITICAL' => 15,
        'ERROR'    => 5,
        'WARNING'  => 2,
        'INFO'     => 0,
    ];

    private array $categoryWeights = [
        'Ledger'            => 0.25,
        'Accounts'          => 0.20,
        'Merchant Learning' => 0.15,
        'Identity Resolver' => 0.15,
        'Dashboard'         => 0.15,
        'Budgets'           => 0.10,
    ];

    public function __construct(?array $deductions = null, ?array $categoryWeights = null)
    {
        if ($deductions !== null) {
            $this->deductions = array_merge($this->deductions, $deductions);
        }
        if ($categoryWeights !== null) {
            $this->categoryWeights = array_merge($this->categoryWeights, $categoryWeights);
        }
    }

    public function calculateScore(IntegrityFindingCollection $findings, array &$categoryScores = []): int
    {
        $findingsByCategory = $findings->all()->groupBy('category');

        $overallWeightedScore = 0.0;
        $totalWeight = 0.0;

        foreach ($this->categoryWeights as $category => $weight) {
            $categoryFindings = $findingsByCategory->get($category, collect());
            
            $score = 100;
            foreach ($categoryFindings as $finding) {
                $severity = strtoupper($finding->severity);
                $deduction = $this->deductions[$severity] ?? 0;
                $score -= $deduction;
            }

            $score = max(0, min(100, $score));
            $categoryScores[$category] = $score;

            $overallWeightedScore += $score * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight > 0) {
            $finalScore = (int) round($overallWeightedScore / $totalWeight);
        } else {
            $finalScore = 100;
        }

        return $finalScore;
    }

    public function getStatus(int $score): string
    {
        if ($score >= 95) {
            return 'READY FOR PRODUCTION';
        }
        if ($score >= 90) {
            return 'READY WITH WARNINGS';
        }
        return 'NOT READY';
    }
}
