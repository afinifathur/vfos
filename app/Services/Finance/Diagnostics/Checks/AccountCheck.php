<?php

namespace App\Services\Finance\Diagnostics\Checks;

use App\Services\Finance\Diagnostics\Contracts\IntegrityCheckInterface;
use App\Services\Finance\Diagnostics\DTO\IntegrityFinding;
use App\Services\Finance\Diagnostics\DTO\IntegrityFindingCollection;
use App\Models\Account;
use App\Models\AccountIdentifier;
use Illuminate\Support\Facades\DB;

class AccountCheck implements IntegrityCheckInterface
{
    public function run(?int $userId = null): IntegrityFindingCollection
    {
        $findings = new IntegrityFindingCollection();

        // 1. Balance Drift Detection & Impossible Negative Balances
        $accountsQuery = Account::query();
        if ($userId) {
            $accountsQuery->where('user_id', $userId);
        }

        $accounts = $accountsQuery->get();

        foreach ($accounts as $account) {
            // Balance Drift Check
            $cachedBalance = (float) $account->total_balance;
            $calculatedBalance = (float) $account->calculateBalance();

            if (abs($cachedBalance - $calculatedBalance) > 0.01) {
                $findings->add(new IntegrityFinding(
                    category: 'Accounts',
                    severity: 'CRITICAL',
                    issue: 'Cached Balance Mismatch (Balance Drift)',
                    affectedObject: "Account '{$account->name}' (ID {$account->id})",
                    currentValue: $cachedBalance,
                    expectedValue: $calculatedBalance,
                    difference: abs($cachedBalance - $calculatedBalance),
                    rootCause: 'Manual database modification, failed transaction hook, or unsynced ledger.',
                    recommendation: 'Run finance:rebuild-balances (or perform manual reconciliation).'
                ));
            }

            // Negative Balance where Impossible (cash, ewallet)
            if (in_array(strtolower($account->type), ['cash', 'ewallet']) && $calculatedBalance < 0.0) {
                $findings->add(new IntegrityFinding(
                    category: 'Accounts',
                    severity: 'CRITICAL',
                    issue: 'Impossible Negative Balance',
                    affectedObject: "Account '{$account->name}' (ID {$account->id})",
                    currentValue: $calculatedBalance,
                    expectedValue: '>= 0.0',
                    difference: abs($calculatedBalance),
                    rootCause: 'Missing income records, untracked cash additions, or over-recorded expenses.',
                    recommendation: 'Audit transactions for this cash/ewallet account to verify source of funds.'
                ));
            }

            // Accounts with no identifiers (ignoring cash/wallet usually, but the prompt says: "accounts with no registered identifiers" as a general rule)
            // Let's flag warning if an active bank account (or ewallet/cash) has zero identifiers.
            $identifierCount = AccountIdentifier::where('account_id', $account->id)->count();
            if ($identifierCount === 0) {
                $findings->add(new IntegrityFinding(
                    category: 'Accounts',
                    severity: 'WARNING',
                    issue: 'No Registered Identifiers',
                    affectedObject: "Account '{$account->name}' (ID {$account->id})",
                    currentValue: 0,
                    expectedValue: '>= 1',
                    difference: null,
                    rootCause: 'Account was created manually but never seeded or mapped to any import identifiers.',
                    recommendation: 'Add at least one identifier via finance:seed-identifiers or administrative UI.'
                ));
            }
        }

        // 2. Active Duplicate Identifiers (same value & provider pointing to different accounts)
        $duplicateQuery = AccountIdentifier::active();
        if ($userId) {
            $duplicateQuery->whereHas('account', fn($q) => $q->where('user_id', $userId));
        }

        $duplicateGroups = $duplicateQuery
            ->select('value', 'provider', DB::raw('COUNT(DISTINCT account_id) as ac_count'))
            ->groupBy('value', 'provider')
            ->having('ac_count', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $accountsLinked = AccountIdentifier::active()
                ->where('value', $group->value)
                ->where('provider', $group->provider)
                ->pluck('account_id')
                ->implode(', ');

            $findings->add(new IntegrityFinding(
                category: 'Identity Resolver',
                severity: 'ERROR',
                issue: 'Duplicate Active Account Identifier',
                affectedObject: "Provider: {$group->provider}, Value: {$group->value}",
                currentValue: "Points to accounts: [{$accountsLinked}]",
                expectedValue: 'Should point to exactly one active account',
                difference: null,
                rootCause: 'Incorrect identifier seeds or duplicate account mapping configuration.',
                recommendation: 'Archive/correct the duplicate identifier pointing to the incorrect account.'
            ));
        }

        // 3. Identifiers pointing to non-existent accounts
        $brokenIdentifiers = AccountIdentifier::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('accounts')
                ->whereColumn('accounts.id', 'account_identifiers.account_id');
        });

        foreach ($brokenIdentifiers->get() as $identifier) {
            $findings->add(new IntegrityFinding(
                category: 'Identity Resolver',
                severity: 'ERROR',
                issue: 'Orphan Account Identifier Reference',
                affectedObject: "Identifier ID {$identifier->id} (Value: {$identifier->value})",
                currentValue: $identifier->account_id,
                expectedValue: 'Valid Account ID',
                difference: null,
                rootCause: 'Account was deleted without deleting or archiving its identifiers.',
                recommendation: 'Purge or archive this identifier record.'
            ));
        }

        return $findings;
    }
}
