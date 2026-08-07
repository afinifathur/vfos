<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Finance\Diagnostics\FinancialIntegrityValidator;

class FinanceHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:health {--user= : Scope validation to a specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a read-only audit of financial integrity across the entire database or a specific user.';

    /**
     * Execute the console command.
     */
    public function handle(FinancialIntegrityValidator $validator): int
    {
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        try {
            $this->info("Running Financial Integrity Audit...");
            $report = $validator->validate($userId);

            $this->newLine();
            $this->comment("=== AUDIT METADATA ===");
            $this->line("Audit ID:        " . $report->metadata['audit_id']);
            $this->line("Generated:       " . $report->metadata['generated_at']);
            $this->line("Git Commit:      " . $report->metadata['git_commit']);
            $this->line("Laravel Version: " . $report->metadata['laravel_version']);
            $this->line("PHP Version:     " . $report->metadata['php_version']);

            $this->newLine();
            $this->comment("=== FINANCIAL HEALTH SCORE ===");
            $statusColor = $report->healthScore >= 95 ? 'info' : ($report->healthScore >= 90 ? 'comment' : 'error');
            $this->$statusColor("Score: {$report->healthScore} / 100 ({$report->certificationStatus})");
            
            $this->newLine();
            $this->line("Category Breakdown:");
            foreach ($report->categorySummary as $cat => $score) {
                $this->line("  - {$cat}: {$score} / 100");
            }

            $this->newLine();
            $this->comment("=== SEVERITY SUMMARY ===");
            $this->line("  Critical : " . $report->severitySummary['CRITICAL']);
            $this->line("  Error    : " . $report->severitySummary['ERROR']);
            $this->line("  Warning  : " . $report->severitySummary['WARNING']);
            $this->line("  Info     : " . $report->severitySummary['INFO']);

            $findings = $report->findings->all();
            if ($findings->isNotEmpty()) {
                $this->newLine();
                $this->comment("=== DISCOVERED FINDINGS ===");
                
                $headers = ['Category', 'Severity', 'Issue', 'Affected Object', 'Current', 'Expected', 'Diff', 'Cause / Recommendation'];
                $tableData = [];

                foreach ($findings as $finding) {
                    $tableData[] = [
                        $finding->category,
                        $finding->severity,
                        $finding->issue,
                        $finding->affectedObject ?? 'N/A',
                        is_scalar($finding->currentValue) ? $finding->currentValue : json_encode($finding->currentValue),
                        is_scalar($finding->expectedValue) ? $finding->expectedValue : json_encode($finding->expectedValue),
                        $finding->difference ?? 'N/A',
                        "Cause: " . ($finding->rootCause ?? 'Unknown') . "\nRec: " . ($finding->recommendation ?? 'N/A')
                    ];
                }

                $this->table($headers, $tableData);
            } else {
                $this->newLine();
                $this->info("No integrity findings discovered. System is clean!");
            }

            // Expose deterministic exit codes
            if ($report->healthScore >= 95) {
                return 0; // READY FOR PRODUCTION
            }
            if ($report->healthScore >= 90) {
                return 1; // READY WITH WARNINGS
            }
            return 2; // NOT READY

        } catch (\Throwable $e) {
            $this->error("INTERNAL VALIDATION ERROR: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 3; // INTERNAL VALIDATION ERROR
        }
    }
}
