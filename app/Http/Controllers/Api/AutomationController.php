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
    public function handleBca(Request $request)
    {
        Log::info('BCA Automation Request:', $request->all());

        if ($request->status === 'Failed') {
            return response()->json(['message' => 'Skipping failed transaction'], 200);
        }

        $sourceAccount = $request->source_account ?? '0244247535';
        $account = Account::where('account_number', $sourceAccount)->first();
        
        // Fallback to searching by name if account_number is not set in the database
        if (!$account) {
            $account = Account::where('name', 'BCA DEBIT')->first();
        }

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
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

        return DB::transaction(function () use ($request, $account, $transactionDate, $type, $category) {
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'type' => $type,
                'transaction_date' => $transactionDate->toDateString(),
                'total_amount' => $request->amount,
                'notes' => "Automated: {$request->merchant} ({$request->type}) - {$request->raw_amount}",
            ]);

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
