<?php

namespace App\Services\Finance\Diagnostics\Checks;

use App\Services\Finance\Diagnostics\Contracts\IntegrityCheckInterface;
use App\Services\Finance\Diagnostics\DTO\IntegrityFinding;
use App\Services\Finance\Diagnostics\DTO\IntegrityFindingCollection;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

class LedgerCheck implements IntegrityCheckInterface
{
    public function run(?int $userId = null): IntegrityFindingCollection
    {
        $findings = new IntegrityFindingCollection();

        // 1. Orphan Transactions (referencing non-existent accounts)
        $orphanTransactions = Transaction::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('accounts')
                ->whereColumn('accounts.id', 'transactions.account_id');
        });

        if ($userId) {
            // Since account_id points to non-existent account, we can't easily scope by user unless we check if it had any user trace, 
            // but we can query transactions that have no matching account at all.
        }

        foreach ($orphanTransactions->get() as $tx) {
            $findings->add(new IntegrityFinding(
                category: 'Ledger',
                severity: 'CRITICAL',
                issue: 'Orphan Transaction',
                affectedObject: "Transaction ID {$tx->id}",
                currentValue: $tx->account_id,
                expectedValue: 'Valid Account ID',
                difference: null,
                rootCause: 'Account was deleted without cascading transactions or invalid manual database entry.',
                recommendation: 'Link the transaction to a valid account or purge it.'
            ));
        }

        // 2. Orphan Transaction Items (referencing non-existent transactions)
        $orphanItems = TransactionItem::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('transactions')
                ->whereColumn('transactions.id', 'transaction_items.transaction_id');
        });

        foreach ($orphanItems->get() as $item) {
            $findings->add(new IntegrityFinding(
                category: 'Ledger',
                severity: 'CRITICAL',
                issue: 'Orphan Transaction Item',
                affectedObject: "Item ID {$item->id}",
                currentValue: $item->transaction_id,
                expectedValue: 'Valid Transaction ID',
                difference: null,
                rootCause: 'Transaction was deleted without cascading items.',
                recommendation: 'Purge the orphaned transaction item.'
            ));
        }

        // 3. Mismatched Transaction Item Sum
        $txs = Transaction::query();
        if ($userId) {
            $txs->whereHas('account', fn($q) => $q->where('user_id', $userId));
        }

        $txs->withSum('transactionItems', 'amount')->chunk(100, function ($chunk) use ($findings) {
            foreach ($chunk as $tx) {
                $itemSum = (float) ($tx->transaction_items_sum_amount ?? 0.0);
                $totalAmount = (float) $tx->total_amount;
                
                if (abs($totalAmount - $itemSum) > 0.01) {
                    $findings->add(new IntegrityFinding(
                        category: 'Ledger',
                        severity: 'CRITICAL',
                        issue: 'Transaction Item Sum Mismatch',
                        affectedObject: "Transaction ID {$tx->id}",
                        currentValue: $itemSum,
                        expectedValue: $totalAmount,
                        difference: abs($totalAmount - $itemSum),
                        rootCause: 'Partial transaction save or manual item modification.',
                        recommendation: 'Re-align the item amounts to match the transaction total.'
                    ));
                }
            }
        });

        // 4. Invalid Transfers
        $invalidTransfers = Transaction::where('type', 'transfer')
            ->where(function ($query) {
                $query->whereNull('to_account_id')
                    ->orWhereColumn('to_account_id', 'account_id');
            });

        if ($userId) {
            $invalidTransfers->whereHas('account', fn($q) => $q->where('user_id', $userId));
        }

        foreach ($invalidTransfers->get() as $tx) {
            $findings->add(new IntegrityFinding(
                category: 'Ledger',
                severity: 'CRITICAL',
                issue: 'Invalid Transfer mapping',
                affectedObject: "Transaction ID {$tx->id}",
                currentValue: "From {$tx->account_id} To " . ($tx->to_account_id ?? 'NULL'),
                expectedValue: 'Distinct source and destination accounts',
                difference: null,
                rootCause: 'Improper transfer classification or missing destination account during import.',
                recommendation: 'Update the transaction destination account or change transaction type.'
            ));
        }

        return $findings;
    }
}
