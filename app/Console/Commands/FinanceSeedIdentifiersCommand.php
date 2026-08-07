<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\AccountIdentifier;
use App\Services\Finance\Identity\AccountProviderResolver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FinanceSeedIdentifiersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'finance:seed-identifiers
                            {--dry-run : Dry run mode, do not persist changes}
                            {--force : Force overwrite conflicting identifiers}
                            {--account= : Only seed identifiers for a specific account ID}';

    /**
     * The console command description.
     */
    protected $description = 'Seed account identifiers from legacy accounts to enable Universal Identity Resolution';

    public function __construct(
        protected AccountProviderResolver $providerResolver
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $accountId = $this->option('account');

        if ($dryRun) {
            $this->info('--- RUNNING IN DRY-RUN MODE (No changes will be saved) ---');
        }

        $query = Account::withoutGlobalScopes();
        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No accounts found to process.');
            return 0;
        }

        $headers = ['Account ID', 'Account Name', 'Type', 'Value', 'Provider', 'Priority', 'Status'];
        $rows = [];
        $stats = [
            'created' => 0,
            'skipped_duplicate' => 0,
            'skipped_conflict' => 0,
            'overwritten' => 0,
        ];

        foreach ($accounts as $account) {
            $provider = $this->providerResolver->resolveProvider($account);

            $candidatesToSeed = [];

            // 1. Account Number
            if (!empty($account->account_number)) {
                $candidatesToSeed[] = [
                    'type' => 'account_number',
                    'value' => $account->account_number,
                    'provider' => $provider,
                    'priority' => 100,
                ];
            }

            // 2. Display Name
            if (!empty($account->name)) {
                $candidatesToSeed[] = [
                    'type' => 'display_name',
                    'value' => $account->name,
                    'provider' => $provider,
                    'priority' => 10,
                ];
            }

            // 3. Bank Alias (e.g. 'BCA' alias for 'BCA Debit')
            if ($provider !== null) {
                $candidatesToSeed[] = [
                    'type' => 'alias',
                    'value' => $provider,
                    'provider' => $provider,
                    'priority' => 50,
                ];
            }

            // 4. Owner Alias
            if (!empty($account->owner)) {
                $candidatesToSeed[] = [
                    'type' => 'alias',
                    'value' => $account->owner,
                    'provider' => null,
                    'priority' => 20,
                ];
            }

            // 5. Future-ready User Email
            $user = $account->user;
            if ($user && !empty($user->email)) {
                $candidatesToSeed[] = [
                    'type' => 'email',
                    'value' => $user->email,
                    'provider' => null,
                    'priority' => 5,
                ];
            }

            foreach ($candidatesToSeed as $candidate) {
                $normValue = $this->normalizeForType($candidate['type'], $candidate['value']);
                
                // Find existing active identifier
                $existing = AccountIdentifier::active()
                    ->where('type', $candidate['type'])
                    ->where('value', $normValue)
                    ->whereHas('account', function ($q) use ($account) {
                        $q->withoutGlobalScopes()->where('user_id', $account->user_id);
                    })
                    ->first();

                if ($existing) {
                    if ($existing->account_id === $account->id &&
                        $existing->provider === $candidate['provider'] &&
                        $existing->priority === $candidate['priority']) {
                        
                        // Exact match duplicate
                        $status = 'Skipped (Duplicate)';
                        $stats['skipped_duplicate']++;
                    } else {
                        // Conflict (points to different account or has different metadata)
                        if ($force) {
                            if (!$dryRun) {
                                DB::transaction(function () use ($existing, $account, $candidate, $normValue) {
                                    // Archive the old one
                                    $existing->update(['archived_at' => now()]);
                                    
                                    // Create new one
                                    AccountIdentifier::create([
                                        'uuid' => (string) Str::uuid(),
                                        'account_id' => $account->id,
                                        'type' => $candidate['type'],
                                        'value' => $normValue,
                                        'original_value' => $candidate['value'],
                                        'provider' => $candidate['provider'],
                                        'priority' => $candidate['priority'],
                                        'source' => 'seeder',
                                    ]);
                                });
                            }
                            $status = 'Overwritten (Force)';
                            $stats['overwritten']++;
                        } else {
                            $status = 'Skipped (Conflict)';
                            $stats['skipped_conflict']++;
                        }
                    }
                } else {
                    // No existing active identifier, safe to create
                    if (!$dryRun) {
                        AccountIdentifier::create([
                            'uuid' => (string) Str::uuid(),
                            'account_id' => $account->id,
                            'type' => $candidate['type'],
                            'value' => $normValue,
                            'original_value' => $candidate['value'],
                            'provider' => $candidate['provider'],
                            'priority' => $candidate['priority'],
                            'source' => 'seeder',
                        ]);
                    }
                    $status = 'Created';
                    $stats['created']++;
                }

                $rows[] = [
                    $account->id,
                    $account->name,
                    $candidate['type'],
                    $candidate['value'],
                    $candidate['provider'] ?? 'NULL',
                    $candidate['priority'],
                    $status,
                ];
            }
        }

        $this->table($headers, $rows);

        $this->info("\nSeeding Execution Report:");
        $this->info("--------------------------");
        $this->info("Created:             {$stats['created']}");
        $this->info("Overwritten (Force): {$stats['overwritten']}");
        $this->info("Skipped (Duplicate): {$stats['skipped_duplicate']}");
        $this->info("Skipped (Conflict):  {$stats['skipped_conflict']}");

        return 0;
    }

    private function normalizeAlphanumeric(string $value): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $value));
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }

    private function normalizeForType(string $type, string $value): string
    {
        switch ($type) {
            case 'email':
                return $this->normalizeEmail($value);
            case 'phone':
                return $this->normalizePhone($value);
            default:
                return $this->normalizeAlphanumeric($value);
        }
    }
}
