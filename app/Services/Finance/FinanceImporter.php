<?php

namespace App\Services\Finance;

use App\Models\InboxMessage;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\DTO\ParserResult;
use App\Services\Parsers\ParserManager;
use App\Services\Finance\Results\ImportResult;
use App\Services\Finance\Results\DuplicateResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceImporter
{
    public function __construct(
        private readonly ParserManager $parserManager
    ) {}

    /**
     * Import a parsed message into the financial ledger.
     */
    public function import(InboxMessage $message, ?ParserResult $result = null): ImportResult
    {
        // 1. Run parser if result is not provided
        if (!$result) {
            try {
                $context = new \App\DTO\ParserContext($message->connector);
                $result = $this->parserManager->parse($message, $context);
            } catch (\App\Exceptions\UnsupportedMessageException $e) {
                $message->update(['status' => InboxMessage::STATUS_FAILED]);
                return new ImportResult('FAILED', null, 'Message not supported by any registered parser: ' . $e->getMessage());
            } catch (\Exception $e) {
                $message->update(['status' => InboxMessage::STATUS_FAILED]);
                return new ImportResult('FAILED', null, 'Parser execution failed: ' . $e->getMessage());
            }
        }

        // 2. Validate
        if (!$result->supported || !$result->parsedTransaction) {
            $message->update(['status' => InboxMessage::STATUS_FAILED]);
            return new ImportResult('FAILED', null, 'Message format not supported or contains no transaction.');
        }

        $parsedTx = $result->parsedTransaction;
        $userId = $message->user_id;

        // 3. Duplicate Detection
        $externalId = $message->google_message_id;
        if ($externalId) {
            $existing = Transaction::where('external_id', $externalId)->first();
            if ($existing) {
                // Already imported: Update status to IMPORTED, store transaction_id, imported_at, and is_duplicate flag
                $raw = $message->raw_payload ?? [];
                $raw['is_duplicate'] = true;

                $message->update([
                    'status' => InboxMessage::STATUS_IMPORTED,
                    'transaction_id' => $existing->id,
                    'imported_at' => now(),
                    'raw_payload' => $raw,
                ]);
                return ImportResult::duplicate($existing->id);
            }
        }

        // 4. Account Mapping
        // Determine primary account number based on flow
        $accountNo = ($parsedTx->flow === 'OUT') ? $parsedTx->sourceAccount : $parsedTx->destinationAccount;
        $account = $this->findAccount($accountNo, $userId, $parsedTx->bank);

        if (!$account) {
            $message->update(['status' => InboxMessage::STATUS_FAILED]);
            return new ImportResult('FAILED', null, 'Mapped financial account not found.');
        }

        // 5. Check if it is a Transfer between user's accounts
        $toAccount = null;
        $txType = ($parsedTx->flow === 'OUT') ? 'expense' : 'income';

        if (strtoupper($parsedTx->type) === 'TRANSFER') {
            $otherAccountNo = ($parsedTx->flow === 'OUT') ? $parsedTx->destinationAccount : $parsedTx->sourceAccount;
            if ($otherAccountNo) {
                $otherAccount = $this->findAccount($otherAccountNo, $userId, $parsedTx->bank);
                // Make sure it's not the same account
                if ($otherAccount && $otherAccount->id !== $account->id) {
                    $toAccount = $otherAccount;
                    $txType = 'transfer';
                }
            }
        }

        // 6. Category mapping: ALWAYS Uncategorized
        $category = Category::firstOrCreate(
            [
                'name' => 'Uncategorized',
                'user_id' => $userId,
                'type' => ($txType === 'income') ? 'income' : 'expense',
            ],
            [
                'owner' => ($userId === 2) ? 'pacar' : 'afin',
                'is_active' => true,
            ]
        );

        // 7. Transaction creation & Item creation
        try {
            $transaction = DB::transaction(function () use (
                $userId,
                $externalId,
                $account,
                $toAccount,
                $txType,
                $parsedTx,
                $category
            ) {
                // Create transaction
                $notes = sprintf(
                    "Imported: %s (%s) - Ref: %s",
                    $parsedTx->merchant,
                    $parsedTx->type,
                    $parsedTx->reference ?? 'N/A'
                );

                $transaction = Transaction::create([
                    'external_id' => $externalId,
                    'account_id' => $account->id,
                    'to_account_id' => $toAccount?->id,
                    'type' => $txType,
                    'transaction_date' => $parsedTx->transactionDate ? $parsedTx->transactionDate->toDateString() : now()->toDateString(),
                    'total_amount' => $parsedTx->amount,
                    'notes' => $notes,
                ]);

                // Create item
                $description = sprintf(
                    "%s - %s",
                    $parsedTx->merchant,
                    $parsedTx->type
                );

                $transaction->transactionItems()->create([
                    'category_id' => $category->id,
                    'description' => $description,
                    'amount' => $parsedTx->amount,
                ]);

                return $transaction;
            });

            // Update inbox status
            $message->update([
                'status' => InboxMessage::STATUS_IMPORTED,
                'transaction_id' => $transaction->id,
                'imported_at' => now(),
            ]);

            return ImportResult::success($transaction->id);

        } catch (\Exception $e) {
            Log::error("Failed to import inbox message {$message->id}: " . $e->getMessage());
            $message->update(['status' => InboxMessage::STATUS_FAILED]);
            return new ImportResult('FAILED', null, 'Database transaction failed: ' . $e->getMessage());
        }
    }

    private function findAccount(?string $accountNo, int $userId, string $bankName): ?Account
    {
        if (empty($accountNo)) {
            return $this->findAccountByBankFallback($userId, $bankName);
        }

        $digits = preg_replace('/[^0-9]/', '', $accountNo);
        if (empty($digits)) {
            return $this->findAccountByBankFallback($userId, $bankName);
        }

        // Priority 1: Match by exact account_number
        $account = Account::where('user_id', $userId)
            ->where('account_number', $digits)
            ->first();

        if ($account) {
            return $account;
        }

        // Try partial match where DB account_number is a suffix of digits
        $accounts = Account::where('user_id', $userId)
            ->whereNotNull('account_number')
            ->get();

        foreach ($accounts as $acc) {
            if (str_ends_with($digits, $acc->account_number) || str_ends_with($acc->account_number, $digits)) {
                return $acc;
            }
        }

        // Priority 2: Fallback by bank name
        return $this->findAccountByBankFallback($userId, $bankName);
    }

    private function findAccountByBankFallback(int $userId, string $bankName): ?Account
    {
        $bank = strtoupper(trim($bankName));
        $query = Account::where('user_id', $userId);

        if ($bank === 'MANDIRI') {
            $query->where('name', 'like', '%MANDIRI%');
        } elseif ($bank === 'BCA') {
            $query->where('name', 'like', '%BCA%');
        } elseif ($bank === 'BNI') {
            $query->where('name', 'like', '%BNI%');
        }

        return $query->first();
    }
}
