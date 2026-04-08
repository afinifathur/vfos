<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutomationController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('BCA Automation Request:', $request->all());

        if ($request->status === 'Failed') {
            return response()->json(['message' => 'Skipping failed transaction'], 200);
        }

        // Assume source_account is provided. If not, use a fallback but this should be supplied by n8n.
        $sourceAccount = $request->source_account;
        
        $account = null;
        if ($sourceAccount) {
            // First try matching exactly by account_number
            $account = Account::where('account_number', $sourceAccount)->first();
            
            // If not found, try matching by account name (e.g., if source_account sent from n8n is 'Gopay')
            if (!$account) {
                 $account = Account::where('name', 'like', "%{$sourceAccount}%")->first();
            }
        }

        if (!$account) {
            return response()->json(['message' => "Account not found for identifier: {$sourceAccount}"], 404);
        }

        // Parse date "15 Mar 2026 16:03:48"
        try {
            $transactionDate = Carbon::parse($request->date);
        } catch (\Exception $e) {
            $transactionDate = now();
        }

        // Guess type
        $type = 'expense';
        $requestType = $request->type ?? '';
        if (str_contains(strtolower($requestType), 'transfer') && str_contains(strtolower($requestType), 'credit')) {
            $type = 'income';
        }
        
        // Default category (create if it doesn't exist)
        $category = Category::firstOrCreate(
            ['name' => 'Uncategorized'],
            ['type' => $type]
        );

        $externalId = $request->external_id;

        return DB::transaction(function () use ($request, $account, $transactionDate, $type, $category, $externalId) {
            $transactionData = [
                'account_id' => $account->id,
                'type' => $type,
                'transaction_date' => $transactionDate->toDateString(),
                'total_amount' => $request->amount,
                'notes' => "Automated: {$request->merchant} ({$request->type}) - {$request->raw_amount}",
            ];

            $transaction = null;

            if ($externalId) {
                $transaction = Transaction::firstOrCreate(
                    ['external_id' => $externalId],
                    $transactionData
                );

                if (!$transaction->wasRecentlyCreated) {
                    return response()->json([
                        'message' => 'Duplicate ignored',
                        'id' => $transaction->id
                    ], 200);
                }
            } else {
                $transaction = Transaction::create($transactionData);
            }

            $transaction->transactionItems()->create([
                'category_id' => $category->id,
                'description' => "{$request->merchant} - {$request->type}",
                'amount' => $request->amount,
            ]);

            return response()->json([
                'message' => 'Transaction automated successfully',
                'id' => $transaction->id
            ], 201);
        });
    }
}
