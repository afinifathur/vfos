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

        $account = Account::where('account_number', '0244247535')->first();
        
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
        if (str_contains(strtolower($request->type), 'transfer') && str_contains(strtolower($request->type), 'credit')) {
            $type = 'income';
        }
        
        // Default category
        $category = Category::where('name', 'Uncategorized')->first() ?? Category::first();

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
