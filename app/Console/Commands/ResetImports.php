<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * DEVELOPMENT / TESTING ONLY.
 *
 * Deletes Gmail-imported finance data (transactions, inbox_messages, sync_jobs)
 * within a given month window. Manual transactions and all master-data tables
 * are never touched.
 *
 * Usage:
 *   php artisan finance:reset-imports             # last 2 months, confirms first
 *   php artisan finance:reset-imports --months=1  # last 1 month
 *   php artisan finance:reset-imports --force     # skip confirmation prompt
 *   php artisan finance:reset-imports --dry-run   # preview only, no deletes
 */
class ResetImports extends Command
{
    protected $signature = 'finance:reset-imports
                            {--months=2 : Number of months of import data to delete (default: 2)}
                            {--force    : Skip confirmation prompt}
                            {--dry-run  : Preview counts only — no data is deleted}';

    protected $description = '[DEV ONLY] Delete Gmail-imported finance data for the last N months. Does not touch manual transactions or master data.';

    public function handle(): int
    {
        $this->warn('');
        $this->warn('⚠️  DEVELOPMENT ONLY — finance:reset-imports');
        $this->warn('This command deletes Gmail-imported finance data. It cannot be undone.');
        $this->warn('');

        $months  = max(1, (int) $this->option('months'));
        $cutoff  = Carbon::now()->startOfDay()->subMonths($months);
        $cutoffStr = $cutoff->toDateTimeString();

        $this->line("Period  : last {$months} month(s)  (from {$cutoffStr} to now)");
        $this->line('');

        // ── Dry-run counts ────────────────────────────────────────────────────

        // Transactions: Gmail-imported within the period
        $txCount = DB::table('transactions')
            ->whereNotNull('external_id')
            ->where('transaction_date', '>=', $cutoff->toDateString())
            ->count();

        // Transaction items are handled by FK CASCADE on transaction delete.
        $itemCount = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->whereNotNull('t.external_id')
            ->where('t.transaction_date', '>=', $cutoff->toDateString())
            ->count();

        // Inbox messages within the period (connector = gmail implied by design)
        $inboxCount = DB::table('inbox_messages')
            ->where('created_at', '>=', $cutoffStr)
            ->count();

        // Sync jobs within the period
        $syncCount = DB::table('sync_jobs')
            ->where('started_at', '>=', $cutoffStr)
            ->count();

        // ── Print preview ─────────────────────────────────────────────────────

        $this->table(
            ['Table', 'Rows to Delete', 'Method'],
            [
                ['transactions',      $txCount,    'explicit DELETE WHERE external_id IS NOT NULL'],
                ['transaction_items', $itemCount,  'FK CASCADE (auto on transaction delete)'],
                ['inbox_messages',    $inboxCount, 'explicit DELETE WHERE created_at >= cutoff'],
                ['sync_jobs',         $syncCount,  'explicit DELETE WHERE started_at >= cutoff'],
            ]
        );

        $this->line('');

        // ── Dry-run exit ──────────────────────────────────────────────────────

        if ($this->option('dry-run')) {
            $this->line('====================================');
            $this->warn('DRY RUN — No data has been deleted.');
            $this->line('====================================');
            return self::SUCCESS;
        }

        // Guard: nothing to do
        if ($txCount === 0 && $inboxCount === 0 && $syncCount === 0) {
            $this->info('Nothing to delete. The database is already clean for the selected period.');
            return self::SUCCESS;
        }

        // ── Confirmation ──────────────────────────────────────────────────────

        if (!$this->option('force')) {
            if (!$this->confirm('Proceed with deletion? This CANNOT be undone.', false)) {
                $this->warn('Aborted. No data was deleted.');
                return self::SUCCESS;
            }
        }

        // ── Execute inside a transaction ──────────────────────────────────────

        $this->line('');
        $this->line('Deleting…');

        try {
            DB::transaction(function () use ($cutoff, $cutoffStr, &$txCount, &$inboxCount, &$syncCount) {

                // Step 1: Resolve the IDs of Gmail transactions to delete first,
                //         so the cascade on transaction_items is triggered correctly.
                $txIds = DB::table('transactions')
                    ->whereNotNull('external_id')
                    ->where('transaction_date', '>=', $cutoff->toDateString())
                    ->pluck('id');

                // Step 2: Delete transactions (transaction_items cascade automatically).
                $txDeleted = DB::table('transactions')
                    ->whereIn('id', $txIds)
                    ->delete();

                // Step 3: Delete inbox_messages within the period.
                $inboxDeleted = DB::table('inbox_messages')
                    ->where('created_at', '>=', $cutoffStr)
                    ->delete();

                // Step 4: Delete sync_jobs within the period.
                $syncDeleted = DB::table('sync_jobs')
                    ->where('started_at', '>=', $cutoffStr)
                    ->delete();

                $txCount    = $txDeleted;
                $inboxCount = $inboxDeleted;
                $syncCount  = $syncDeleted;
            });
        } catch (\Throwable $e) {
            $this->error('');
            $this->error('An error occurred — the transaction was rolled back. No data was deleted.');
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }

        // ── Final summary ─────────────────────────────────────────────────────

        $this->line('');
        $this->info('=== Reset Complete ===');
        $this->line(sprintf('Transactions deleted  : %d', $txCount));
        $this->line(sprintf('Transaction Items     : cascade'));
        $this->line(sprintf('Inbox Messages        : %d', $inboxCount));
        $this->line(sprintf('Sync Jobs             : %d', $syncCount));
        $this->line('');
        $this->info('Completed successfully.');

        return self::SUCCESS;
    }
}
