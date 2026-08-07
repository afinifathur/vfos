<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InboxMessage;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\MerchantAlias;
use App\Services\Finance\FinanceImporter;
use App\Services\Finance\Identity\IdentityResolutionStatistics;
use App\Services\Finance\Identity\ImportResolutionTrace;
use App\Services\Finance\Kernel\FinancialKernelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class FinanceReplayImportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'finance:replay-imports
                            {--resolver=compare : Which resolver mode to test (legacy, identity, compare)}
                            {--since= : Replay messages imported since this date (YYYY-MM-DD)}
                            {--last= : Limit to last N messages}
                            {--all : Replay all messages}
                            {--full : Enable detailed metrics and full fingerprint comparison}';

    /**
     * The console command description.
     */
    protected $description = 'Replay and compare historical financial message imports with deterministic fingerprints and benchmarking';

    public function __construct(
        protected FinanceImporter $importer,
        protected FinancialKernelService $kernelService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $resolverMode = $this->option('resolver');
        $since = $this->option('since');
        $last = $this->option('last');
        $all = $this->option('all');

        $query = InboxMessage::where('status', InboxMessage::STATUS_IMPORTED);

        if ($since) {
            $query->where('imported_at', '>=', $since);
        }

        if ($last) {
            $query->latest('imported_at')->limit((int) $last);
        }

        if (!$since && !$last && !$all) {
            // Default to since 2026-06-01 for backward compatibility
            $query->where('imported_at', '>=', '2026-06-01');
            $this->info("No scope provided, defaulting to messages since 2026-06-01.");
        }

        $messages = $query->get();

        if ($messages->isEmpty()) {
            $this->warn("No imported messages found matching the criteria.");
            return 0;
        }

        $this->info("Found {$messages->count()} messages to replay.");

        if ($resolverMode === 'compare') {
            return $this->runComparison($messages);
        }

        return $this->runSingleMode($messages, $resolverMode);
    }

    protected function runSingleMode($messages, $mode): int
    {
        $useIdentity = ($mode === 'identity');
        Config::set('finance.use_identity_resolver', $useIdentity);
        IdentityResolutionStatistics::reset();
        ImportResolutionTrace::reset();

        $this->info("Replaying in single mode: " . ($useIdentity ? 'Identity Resolver' : 'Legacy Lookup'));

        $successCount = 0;
        $failedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($messages as $message) {
                if ($message->google_message_id) {
                    Transaction::where('external_id', $message->google_message_id)->delete();
                }

                // Reset message status to simulate import
                $message->status = InboxMessage::STATUS_FAILED;
                $message->transaction_id = null;

                // Replay the import
                $result = $this->importer->import($message);

                if ($result->status === 'SUCCESS') {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
        } finally {
            DB::rollBack();
        }

        $this->info("\nExecution Summary:");
        $this->info("-------------------");
        $this->info("Total Replayed: {$messages->count()}");
        $this->info("Imported:       {$successCount}");
        $this->info("Failed:         {$failedCount}");

        if ($useIdentity) {
            $this->printStats();
        }

        return 0;
    }

    protected function runComparison($messages): int
    {
        $this->info("Replaying in COMPARISON mode (Parallel execution with zero database mutations)...");

        $mismatches = [];
        $totalCompared = 0;
        $identicalCount = 0;

        IdentityResolutionStatistics::reset();
        ImportResolutionTrace::reset();

        // Subsystem benchmarking metrics arrays (in milliseconds)
        $benchmarks = [
            'resolver' => [],
            'importer' => [],
            'kernel'   => [],
            'merchant' => [],
            'database' => [],
            'total'    => [],
        ];

        // Overall Fingerprints
        $legacyFingerprints = [];
        $identityFingerprints = [];

        foreach ($messages as $message) {
            $legacyTx = null;
            $identityTx = null;
            $msgTotalStart = microtime(true);

            // Layered trace variables
            $legacyResolverPassed = false;
            $identityResolverPassed = false;

            // 1. Run Legacy
            $dbStart = microtime(true);
            DB::transaction(function () use ($message, &$legacyTx, &$legacyResolverPassed, &$legacyFingerprints) {
                Config::set('finance.use_identity_resolver', false);
                
                if ($message->google_message_id) {
                    Transaction::where('external_id', $message->google_message_id)->delete();
                }
                
                // Clone message state for isolation
                $msgClone = $message->replicate();
                $msgClone->id = $message->id;
                $msgClone->status = InboxMessage::STATUS_FAILED;
                $msgClone->transaction_id = null;

                $result = $this->importer->import($msgClone);
                if ($result->status === 'SUCCESS') {
                    $legacyTx = Transaction::find($result->transactionId);
                    $legacyResolverPassed = true;
                }
                
                // Capture legacy fingerprints inside transaction
                $legacyFingerprints[] = $this->generateCurrentFingerprints();

                DB::rollBack(); // Always rollback legacy
            });
            $benchmarks['database'][] = (microtime(true) - $dbStart) * 1000.0;

            // 2. Run Identity
            $idStart = microtime(true);
            DB::transaction(function () use ($message, &$identityTx, &$identityResolverPassed, &$identityFingerprints, &$benchmarks) {
                Config::set('finance.use_identity_resolver', true);
                
                if ($message->google_message_id) {
                    Transaction::where('external_id', $message->google_message_id)->delete();
                }
                
                $msgClone = $message->replicate();
                $msgClone->id = $message->id;
                $msgClone->status = InboxMessage::STATUS_FAILED;
                $msgClone->transaction_id = null;

                $impStart = microtime(true);
                $result = $this->importer->import($msgClone);
                $benchmarks['importer'][] = (microtime(true) - $impStart) * 1000.0;

                if ($result->status === 'SUCCESS') {
                    $identityTx = Transaction::find($result->transactionId);
                    $identityResolverPassed = true;
                }

                // Capture identity fingerprints inside transaction
                $identityFingerprints[] = $this->generateCurrentFingerprints();

                // Benchmarking Kernel & Merchant inside loop
                $kStart = microtime(true);
                if ($identityTx) {
                    $identityTx->account->calculateBalance();
                }
                $benchmarks['kernel'][] = (microtime(true) - $kStart) * 1000.0;

                $mStart = microtime(true);
                if ($identityTx && isset($identityTx->metadata['merchant_key'])) {
                    MerchantAlias::where('merchant_key', $identityTx->metadata['merchant_key'])->first();
                }
                $benchmarks['merchant'][] = (microtime(true) - $mStart) * 1000.0;

                DB::rollBack(); // Always rollback identity
            });
            $benchmarks['total'][] = (microtime(true) - $idStart) * 1000.0;

            // Collect Resolver duration from traces
            $traces = ImportResolutionTrace::getTraces();
            if (!empty($traces)) {
                $lastTrace = end($traces);
                $benchmarks['resolver'][] = $lastTrace['elapsed_ms'] ?? 0.0;
            } else {
                $benchmarks['resolver'][] = 0.0;
            }

            // 3. Layered Verification Checks
            $diffs = [];
            
            // Layer 1: Identity Resolution check
            if ($legacyResolverPassed !== $identityResolverPassed) {
                $diffs['identity_resolution'] = [
                    'legacy' => $legacyResolverPassed ? 'RESOLVED' : 'FAILED',
                    'identity' => $identityResolverPassed ? 'RESOLVED' : 'FAILED'
                ];
            }

            // Layer 2: Finance Importer / Transaction property matches
            $propDiffs = $this->compareTransactions($legacyTx, $identityTx);
            if (!empty($propDiffs)) {
                $diffs = array_merge($diffs, $propDiffs);
            }

            if (empty($diffs)) {
                $identicalCount++;
            } else {
                $mismatches[] = [
                    'message_id' => $message->id,
                    'google_id' => $message->google_message_id,
                    'subject' => $message->subject,
                    'diffs' => $diffs
                ];
            }

            $totalCompared++;
        }

        // Layer 6: Fingerprint Comparison
        $fingerprintMismatch = false;
        $legacyFingerprintHashes = $this->hashFingerprintBatch($legacyFingerprints);
        $identityFingerprintHashes = $this->hashFingerprintBatch($identityFingerprints);

        if ($legacyFingerprintHashes !== $identityFingerprintHashes) {
            $fingerprintMismatch = true;
        }

        // Print Certification Summary
        $this->newLine();
        $this->comment("=================================================");
        $this->comment("            REPLAY COMPARISON REPORT             ");
        $this->comment("=================================================");
        
        $this->line("Messages compared:          {$totalCompared}");
        $this->line("Identical matches:          {$identicalCount}");
        $this->line("Mismatches found:           " . count($mismatches));
        $this->line("Fingerprint check:          " . ($fingerprintMismatch ? '<error>FAIL</error>' : '<info>PASS</info>'));

        if ($fingerprintMismatch) {
            $this->warn("\nFingerprint Details:");
            $this->warn("  Legacy hashes:   " . json_encode($legacyFingerprintHashes));
            $this->warn("  Identity hashes: " . json_encode($identityFingerprintHashes));
        }

        if (count($mismatches) > 0) {
            $this->warn("\nMismatch Details:");
            foreach ($mismatches as $mismatch) {
                $this->warn("Message ID: {$mismatch['message_id']} (Google ID: {$mismatch['google_id']})");
                foreach ($mismatch['diffs'] as $field => $val) {
                    $this->warn("  * Mismatch in [{$field}]: Legacy = " . json_encode($val['legacy']) . " | Identity = " . json_encode($val['identity']));
                }
            }
        }

        // Display Benchmarking Metrics
        $this->newLine();
        $this->comment("=== PERFORMANCE BENCHMARKS (ms) ===");
        $headers = ['Subsystem', 'Min', 'Max', 'Avg', 'Median'];
        $benchRows = [];
        foreach ($benchmarks as $subsystem => $times) {
            if (empty($times)) {
                $benchRows[] = [ucfirst($subsystem), '0.00', '0.00', '0.00', '0.00'];
                continue;
            }
            $min = min($times);
            $max = max($times);
            $avg = array_sum($times) / count($times);
            $median = $this->calculateMedian($times);

            $benchRows[] = [
                ucfirst($subsystem),
                sprintf('%.2f', $min),
                sprintf('%.2f', $max),
                sprintf('%.2f', $avg),
                sprintf('%.2f', $median)
            ];
        }
        $this->table($headers, $benchRows);

        if (count($mismatches) > 0 || $fingerprintMismatch) {
            return 1;
        }

        $this->info("\n✅ SUCCESS: Parallel comparison completed with zero differences!");
        return 0;
    }

    private function generateCurrentFingerprints(): array
    {
        // 1. Ledger Data
        $ledgerData = Transaction::orderBy('id')->get()->map(fn($t) => [
            'account_id' => $t->account_id,
            'to_account_id' => $t->to_account_id,
            'total_amount' => (float) $t->total_amount,
            'type' => $t->type,
            'transaction_date' => $t->transaction_date instanceof \DateTimeInterface 
                ? $t->transaction_date->format('Y-m-d') 
                : (is_string($t->transaction_date) ? substr($t->transaction_date, 0, 10) : null),
        ])->toArray();

        // 2. Dashboard aggregates (last 12 months)
        $dashboardData = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $dashboardData[] = [
                'month'   => $date->month,
                'year'    => $date->year,
                'income'  => (float) $this->kernelService->getMonthlyIncome($date->month, $date->year),
                'expense' => (float) $this->kernelService->getMonthlyExpense($date->month, $date->year),
            ];
        }

        // 3. Net Worth
        $netWorthData = [
            'liquid' => (float) $this->kernelService->calculateLiquidNetWorth(),
        ];

        // 4. Merchant Mappings
        $merchantData = MerchantAlias::orderBy('merchant_key')->get()->map(fn($a) => [
            'merchant_key'   => $a->merchant_key,
            'category_id'    => $a->category_id,
            'subcategory_id' => $a->subcategory_id,
        ])->toArray();

        return [
            'ledger'    => hash('sha256', json_encode($ledgerData)),
            'dashboard' => hash('sha256', json_encode($dashboardData)),
            'networth'  => hash('sha256', json_encode($netWorthData)),
            'merchant'  => hash('sha256', json_encode($merchantData)),
        ];
    }

    private function hashFingerprintBatch(array $batch): array
    {
        if (empty($batch)) {
            return [
                'ledger'    => hash('sha256', ''),
                'dashboard' => hash('sha256', ''),
                'networth'  => hash('sha256', ''),
                'merchant'  => hash('sha256', ''),
            ];
        }

        // Combine hashes from each run deterministically
        $ledgers = '';
        $dashboards = '';
        $networths = '';
        $merchants = '';

        foreach ($batch as $run) {
            $ledgers .= $run['ledger'];
            $dashboards .= $run['dashboard'];
            $networths .= $run['networth'];
            $merchants .= $run['merchant'];
        }

        return [
            'ledger'    => hash('sha256', $ledgers),
            'dashboard' => hash('sha256', $dashboards),
            'networth'  => hash('sha256', $networths),
            'merchant'  => hash('sha256', $merchants),
        ];
    }

    private function calculateMedian(array $numbers): float
    {
        if (empty($numbers)) {
            return 0.0;
        }
        sort($numbers);
        $count = count($numbers);
        $middle = (int) floor($count / 2);
        if ($count % 2 === 0) {
            return ($numbers[$middle - 1] + $numbers[$middle]) / 2.0;
        }
        return $numbers[$middle];
    }

    protected function compareTransactions(?Transaction $legacy, ?Transaction $identity): array
    {
        $diffs = [];

        if ($legacy === null && $identity !== null) {
            $diffs['status'] = ['legacy' => 'FAILED', 'identity' => 'SUCCESS'];
            return $diffs;
        }

        if ($legacy !== null && $identity === null) {
            $diffs['status'] = ['legacy' => 'SUCCESS', 'identity' => 'FAILED'];
            return $diffs;
        }

        if ($legacy === null && $identity === null) {
            return $diffs; // Both failed, matching
        }

        // Compare Account
        if ($legacy->account_id !== $identity->account_id) {
            $diffs['account_id'] = [
                'legacy' => $legacy->account_id . ' (' . Account::withoutGlobalScopes()->find($legacy->account_id)?->name . ')',
                'identity' => $identity->account_id . ' (' . Account::withoutGlobalScopes()->find($identity->account_id)?->name . ')'
            ];
        }

        // Compare Amount
        if (floatval($legacy->total_amount) !== floatval($identity->total_amount)) {
            $diffs['total_amount'] = ['legacy' => $legacy->total_amount, 'identity' => $identity->total_amount];
        }

        // Compare Type
        if ($legacy->type !== $identity->type) {
            $diffs['type'] = ['legacy' => $legacy->type, 'identity' => $identity->type];
        }

        // Compare Category
        $legacyCategory = $legacy->metadata['category_id'] ?? null;
        $identityCategory = $identity->metadata['category_id'] ?? null;
        if ($legacyCategory !== $identityCategory) {
            $diffs['category_id'] = [
                'legacy' => $legacyCategory . ' (' . Category::find($legacyCategory)?->name . ')',
                'identity' => $identityCategory . ' (' . Category::find($identityCategory)?->name . ')'
            ];
        }

        // Compare Subcategory
        $legacySubcategory = $legacy->metadata['subcategory_id'] ?? null;
        $identitySubcategory = $identity->metadata['subcategory_id'] ?? null;
        if ($legacySubcategory !== $identitySubcategory) {
            $diffs['subcategory_id'] = [
                'legacy' => $legacySubcategory . ' (' . Subcategory::find($legacySubcategory)?->name . ')',
                'identity' => $identitySubcategory . ' (' . Subcategory::find($identitySubcategory)?->name . ')'
            ];
        }

        // Compare Merchant Key
        $legacyMerchant = $legacy->metadata['merchant_key'] ?? null;
        $identityMerchant = $identity->metadata['merchant_key'] ?? null;
        if ($legacyMerchant !== $identityMerchant) {
            $diffs['merchant_key'] = ['legacy' => $legacyMerchant, 'identity' => $identityMerchant];
        }

        return $diffs;
    }

    protected function printStats()
    {
        $counts = IdentityResolutionStatistics::getCounts();
        $perf = IdentityResolutionStatistics::getPerformanceMetrics();

        $this->info("\nIdentity Resolution Stats:");
        $this->info("---------------------------");
        $this->info("Exact Match:           {$counts['exact_match']}");
        $this->info("Virtual Account Match: {$counts['virtual_account_match']}");
        $this->info("Phone Match:           {$counts['phone']}");
        $this->info("Alias Match:           {$counts['alias']}");
        $this->info("Display Name Match:    {$counts['display_name']}");
        $this->info("Legacy Fallback:       {$counts['legacy_fallback']}");
        $this->info("No Match:              {$counts['no_match']}");
        $this->info("Ambiguous:             {$counts['ambiguous']}");

        $this->info("\nPerformance Metrics:");
        $this->info("--------------------");
        $this->info("Total Lookups:         {$perf['count']}");
        $this->info("Average time:          {$perf['average']} ms");
        $this->info("Median time:           {$perf['median']} ms");
        $this->info("P95 time:              {$perf['p95']} ms");
        $this->info("Slowest lookup:        {$perf['slowest']} ms");
    }
}
