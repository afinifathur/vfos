<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutomationController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Automation Request:', $request->all());

        // =======================
        // SKIP FAILED
        // =======================
        if (($request->status ?? '') === 'Failed') {
            return response()->json([
                'message' => 'Skipping failed transaction'
            ], 200);
        }

        // =======================
        // USER CONTEXT
        // =======================
        $userTag = strtoupper(trim($request->user_tag ?? 'SELF'));

        // Mapping user
        $userId = ($userTag === 'PACAR') ? 2 : 1;

        // =======================
        // ACCOUNT MATCHING
        // PRIORITY:
        // 1. account_number (last digits)
        // 2. bank name fallback
        // =======================
        $bank = strtoupper(trim($request->bank ?? ''));
        $sourceAccount = $request->source_account ?? null;

        $account = null;
        $lastDigits = null;

        // Extract only numbers from masked account
        // Example:
        // ****5882 -> 5882
        // 0244xxxx35 -> 0035 / 35 (must match DB format)
        if ($sourceAccount) {
            $lastDigits = preg_replace('/[^0-9]/', '', $sourceAccount);
        }

        // PRIORITY 1:
        // Match by exact account_number
        if ($lastDigits) {
            $account = Account::where('user_id', $userId)
                ->where('account_number', $lastDigits)
                ->first();
        }

        // PRIORITY 2:
        // Fallback by bank name
        if (!$account) {
            $query = Account::where('user_id', $userId);

            if ($bank === 'MANDIRI') {
                $query->where('name', 'like', '%MANDIRI%');
            } elseif ($bank === 'BCA') {
                $query->where('name', 'like', '%BCA%');
            } elseif ($bank === 'BNI') {
                $query->where('name', 'like', '%BNI%');
            }

            $account = $query->orderBy('id', 'asc')->first();
        }

        // Account not found
        if (!$account) {
            return response()->json([
                'message' => 'Account not found',
                'user_id' => $userId,
                'bank' => $bank,
                'source_account' => $sourceAccount,
                'last_digits' => $lastDigits,
            ], 404);
        }

        Log::info('Matched Account', [
            'user_id' => $userId,
            'account_id' => $account->id,
            'account_name' => $account->name,
            'last_digits' => $lastDigits,
        ]);

        // =======================
        // DATE PARSING
        // =======================
        try {
            $transactionDate = Carbon::parse($request->date);
        } catch (\Exception $e) {
            $transactionDate = now();
        }

        // =======================
        // TYPE DETECTION
        // =======================
        $type = $request->type ?? 'expense';

        // =======================
        // CATEGORY
        // =======================
        $category = Category::firstOrCreate(
            ['name' => 'Uncategorized'],
            ['type' => $type]
        );

        $externalId = $request->external_id;

        // =======================
        // DATABASE TRANSACTION
        // =======================
        return DB::transaction(function () use (
            $request,
            $account,
            $transactionDate,
            $type,
            $category,
            $externalId
        ) {
            // =======================
            // VALIDATION
            // =======================
            if (!$request->amount) {
                return response()->json([
                    'message' => 'Invalid amount, skipped'
                ], 200);
            }

            $transactionData = [
                'account_id'       => $account->id,
                'type'             => $type,
                'transaction_date' => $transactionDate->toDateString(),
                'total_amount'     => $request->amount,
                'notes'            => "Automated: {$request->merchant} ({$request->type}) - {$request->raw_amount}",
            ];

            $transaction = null;

            // =======================
            // DUPLICATE PROTECTION
            // =======================
            if ($externalId) {
                $transaction = Transaction::firstOrCreate(
                    ['external_id' => $externalId],
                    $transactionData
                );

                if (!$transaction->wasRecentlyCreated) {
                    return response()->json([
                        'message' => 'Duplicate ignored',
                        'id'      => $transaction->id
                    ], 200);
                }
            } else {
                $transaction = Transaction::create($transactionData);
            }

            // =======================
            // CREATE ITEM
            // =======================
            $transaction->transactionItems()->create([
                'category_id' => $category->id,
                'description' => "{$request->merchant} - {$request->type}",
                'amount'      => $request->amount,
            ]);

            return response()->json([
                'message' => 'Transaction automated successfully',
                'id'      => $transaction->id
            ], 201);

        }, 5); // retry up to 5x if deadlock
    }
}