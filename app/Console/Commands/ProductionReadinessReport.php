<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Finance\Diagnostics\FinancialIntegrityValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

class ProductionReadinessReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:production-report {--user= : Scope report to a specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the final production readiness and financial integrity certification report.';

    /**
     * Execute the console command.
     */
    public function handle(FinancialIntegrityValidator $validator): int
    {
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        try {
            $this->info("Generating Production Readiness & Certification Report...");
            
            // 1. Run Financial Integrity Audit
            $report = $validator->validate($userId);

            // 2. Perform Production Configuration Audit
            $configAudit = $this->runConfigAudit();

            // 3. Quick Replay Verification (last 10 messages)
            $replayStatus = 'PASS';
            $replayOutput = '';
            try {
                $this->info("Running quick replay verification of last 10 messages...");
                $exitCode = Artisan::call('finance:replay-imports', [
                    '--resolver' => 'compare',
                    '--last'     => 10,
                    '--full'     => true,
                ]);
                $replayOutput = Artisan::output();
                if ($exitCode !== 0) {
                    $replayStatus = 'FAIL';
                }
            } catch (\Throwable $e) {
                $replayStatus = 'FAIL';
                $replayOutput = 'Error running replay command: ' . $e->getMessage();
            }

            // 4. Output Certification Summary
            $this->newLine();
            $this->comment("=================================================");
            $this->comment("     FINANCIAL INTEGRITY CERTIFICATION REPORT    ");
            $this->comment("=================================================");
            
            $this->newLine();
            $this->comment("=== AUDIT METADATA ===");
            $this->line("Audit ID:        " . $report->metadata['audit_id']);
            $this->line("Generated:       " . $report->metadata['generated_at']);
            $this->line("Git Commit:      " . $report->metadata['git_commit']);
            $this->line("Laravel Version: " . $report->metadata['laravel_version']);
            $this->line("PHP Version:     " . $report->metadata['php_version']);

            $this->newLine();
            $this->comment("=== CERTIFICATION SUMMARY ===");
            
            $ledgerPass = ($report->severitySummary['CRITICAL'] === 0) ? 'PASS' : 'FAIL';
            $accountPass = ($report->categorySummary['Accounts'] >= 90) ? 'PASS' : 'FAIL';
            $netWorthPass = ($report->categorySummary['Dashboard'] >= 95) ? 'PASS' : 'FAIL';
            $resolverPass = ($report->categorySummary['Identity Resolver'] >= 90) ? 'PASS' : 'FAIL';
            $merchantPass = ($report->categorySummary['Merchant Learning'] >= 90) ? 'PASS' : 'FAIL';
            $dashboardPass = ($report->categorySummary['Dashboard'] >= 90) ? 'PASS' : 'FAIL';
            $configPass = collect($configAudit)->every(fn($item) => $item['status'] === 'PASS') ? 'PASS' : 'FAIL';

            $this->line("Ledger Integrity:      " . $this->statusLabel($ledgerPass));
            $this->line("Account Integrity:     " . $this->statusLabel($accountPass));
            $this->line("Net Worth Coherence:   " . $this->statusLabel($netWorthPass));
            $this->line("Identity Resolver:     " . $this->statusLabel($resolverPass));
            $this->line("Merchant Learning:     " . $this->statusLabel($merchantPass));
            $this->line("Dashboard Consistency: " . $this->statusLabel($dashboardPass));
            $this->line("Replay Verification:   " . $this->statusLabel($replayStatus));
            $this->line("Configuration Audit:   " . $this->statusLabel($configPass));

            $this->newLine();
            $this->comment("=== RUNTIME CONFIGURATION CHECK ===");
            $configHeaders = ['Parameter', 'Status', 'Details'];
            $configRows = [];
            foreach ($configAudit as $param => $item) {
                $configRows[] = [
                    $param,
                    $item['status'],
                    $item['detail']
                ];
            }
            $this->table($configHeaders, $configRows);

            if ($replayStatus === 'FAIL') {
                $this->newLine();
                $this->error("Replay Verification failed. Output:");
                $this->line($replayOutput);
            }

            $this->newLine();
            $this->comment("=== FINANCIAL HEALTH SCORE ===");
            $statusColor = $report->healthScore >= 95 ? 'info' : ($report->healthScore >= 90 ? 'comment' : 'error');
            $this->$statusColor("Score: {$report->healthScore} / 100 ({$report->certificationStatus})");
            
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

            // Overall Readiness calculation
            $this->newLine();
            if ($report->healthScore >= 95 && $replayStatus === 'PASS' && $ledgerPass === 'PASS') {
                $this->info(">>> FINAL STATUS: READY FOR PRODUCTION <<<");
                return 0; // READY FOR PRODUCTION
            } elseif ($report->healthScore >= 90) {
                $this->comment(">>> FINAL STATUS: READY WITH WARNINGS <<<");
                return 1; // READY WITH WARNINGS
            } else {
                $this->error(">>> FINAL STATUS: NOT READY <<<");
                return 2; // NOT READY
            }

        } catch (\Throwable $e) {
            $this->error("INTERNAL VALIDATION ERROR: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 3; // INTERNAL VALIDATION ERROR
        }
    }

    private function statusLabel(string $status): string
    {
        return $status === 'PASS' ? '<info>PASS</info>' : '<error>FAIL</error>';
    }

    private function runConfigAudit(): array
    {
        $audit = [];

        // 1. APP_ENV
        $env = config('app.env');
        $audit['APP_ENV'] = [
            'status' => $env === 'production' ? 'PASS' : 'PASS (dev)',
            'detail' => "Current environment: {$env}"
        ];

        // 2. Database connection
        try {
            DB::connection()->getPdo();
            $audit['Database'] = [
                'status' => 'PASS',
                'detail' => 'Database connection successful.'
            ];
        } catch (\Throwable $e) {
            $audit['Database'] = [
                'status' => 'FAIL',
                'detail' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // 3. Queue Driver
        $queueDriver = config('queue.default');
        $audit['Queue'] = [
            'status' => $queueDriver !== 'sync' ? 'PASS' : 'WARNING',
            'detail' => "Current queue driver: {$queueDriver} (synchronous 'sync' is not recommended for production imports)."
        ];

        // 4. Scheduler
        $schedule = app(Schedule::class);
        $eventCount = count($schedule->events());
        $audit['Scheduler'] = [
            'status' => $eventCount > 0 ? 'PASS' : 'WARNING',
            'detail' => "Scheduler has {$eventCount} events registered."
        ];

        // 5. Mail Configuration
        $mailDriver = config('mail.default');
        $audit['Mail Configuration'] = [
            'status' => $mailDriver !== 'log' ? 'PASS' : 'WARNING',
            'detail' => "Mail driver is set to: {$mailDriver}."
        ];

        // 6. Identity Resolver Feature Flag
        $resolverActive = config('finance.use_identity_resolver', false);
        $audit['Identity Resolver Enabled'] = [
            'status' => $resolverActive ? 'PASS' : 'FAIL',
            'detail' => 'Feature flag FINANCE_USE_IDENTITY_RESOLVER is ' . ($resolverActive ? 'ENABLED' : 'DISABLED') . '.'
        ];

        return $audit;
    }
}
