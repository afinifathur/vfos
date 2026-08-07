<?php

namespace App\Services\Finance\Diagnostics;

use App\Services\Finance\Diagnostics\DTO\FinancialIntegrityReport;
use App\Services\Finance\Diagnostics\DTO\IntegrityFindingCollection;
use App\Services\Finance\Diagnostics\Checks\LedgerCheck;
use App\Services\Finance\Diagnostics\Checks\AccountCheck;
use App\Services\Finance\Diagnostics\Checks\MerchantLearningCheck;
use App\Services\Finance\Diagnostics\Checks\DashboardCheck;
use App\Services\Finance\Kernel\FinancialKernelService;
use Illuminate\Support\Str;

class FinancialIntegrityValidator
{
    private array $checks = [];

    public function __construct(
        protected FinancialKernelService $kernelService,
        protected FinancialHealthPolicy $healthPolicy
    ) {
        // Register default check modules
        $this->registerCheck(new LedgerCheck());
        $this->registerCheck(new AccountCheck());
        $this->registerCheck(new MerchantLearningCheck());
        $this->registerCheck(new DashboardCheck($this->kernelService));
    }

    public function registerCheck($check): void
    {
        $this->checks[] = $check;
    }

    /**
     * Perform the financial integrity audit and return a strongly typed report DTO.
     */
    public function validate(?int $userId = null): FinancialIntegrityReport
    {
        $allFindings = new IntegrityFindingCollection();

        // 1. Run all checks
        foreach ($this->checks as $check) {
            $findings = $check->run($userId);
            $allFindings = $allFindings->merge($findings);
        }

        // 2. Compute Health Score & Category breakdown
        $categoryScores = [];
        $healthScore = $this->healthPolicy->calculateScore($allFindings, $categoryScores);
        $certificationStatus = $this->healthPolicy->getStatus($healthScore);

        // 3. Summarize by Severity
        $severitySummary = [
            'CRITICAL' => 0,
            'ERROR'    => 0,
            'WARNING'  => 0,
            'INFO'     => 0,
        ];
        foreach ($allFindings->all() as $finding) {
            $sev = strtoupper($finding->severity);
            if (array_key_exists($sev, $severitySummary)) {
                $severitySummary[$sev]++;
            }
        }

        // 4. Gather Metadata
        $metadata = [
            'audit_id'        => date('Ymd-His') . '-' . strtoupper(Str::random(4)),
            'generated_at'    => date('Y-m-d H:i:s'),
            'git_commit'      => $this->getGitCommit(),
            'laravel_version' => app()->version(),
            'php_version'     => PHP_VERSION,
        ];

        return new FinancialIntegrityReport(
            metadata: $metadata,
            healthScore: $healthScore,
            certificationStatus: $certificationStatus,
            severitySummary: $severitySummary,
            categorySummary: $categoryScores,
            findings: $allFindings
        );
    }

    private function getGitCommit(): string
    {
        try {
            $commit = shell_exec('git log -1 --format=%h');
            return $commit ? trim($commit) : 'N/A';
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }
}
